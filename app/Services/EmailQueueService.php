<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailHistory;
use App\Models\EmailQueue;
use App\Models\SmtpSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailQueueService
{
    public function processBatch(int $batchSize = 20, ?int $campaignId = null): int
    {
        $maxAttempts = 3;
        $query = EmailQueue::where('status', 'pending')
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('id');
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }
        $rows = $query->limit($batchSize)->get();
        if ($rows->isEmpty()) {
            return 0;
        }
        EmailQueue::whereIn('id', $rows->pluck('id'))
            ->update(['status' => 'processing', 'attempts' => DB::raw('attempts + 1')]);

        $smtp = SmtpSetting::getSettings();
        if ($smtp) {
            config(['mail.mailers.smtp.host'       => $smtp->host]);
            config(['mail.mailers.smtp.port'       => $smtp->port]);
            config(['mail.mailers.smtp.encryption' => $smtp->encryption === 'none' ? null : $smtp->encryption]);
            config(['mail.mailers.smtp.username'   => $smtp->username]);
            config(['mail.mailers.smtp.password'   => $smtp->password]);
            config(['mail.from.address'            => $smtp->from_email]);
            config(['mail.from.name'               => $smtp->from_name]);
        }

        $processed = 0;
        foreach ($rows as $row) {
            try {
                Mail::html($row->message, function ($message) use ($row) {
                    $message->to($row->recipient_email)->subject($row->subject);
                    if ($row->attachment_path && is_file(base_path($row->attachment_path))) {
                        $message->attach(base_path($row->attachment_path));
                    }
                });
                $row->update(['status' => 'sent', 'processed_at' => now(), 'last_error' => null]);
                CampaignRecipient::where('id', $row->campaign_recipient_id)
                    ->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);
                $this->updateHistory($row->campaign_id, $row->recipient_email, 'sent', null);
            } catch (\Throwable $e) {
                $error    = substr($e->getMessage(), 0, 500);
                $attempts = (int) $row->fresh()->attempts;
                $failPerm = $attempts >= $maxAttempts;
                $row->update(['status' => $failPerm ? 'failed' : 'pending', 'last_error' => $error, 'processed_at' => now()]);
                if ($failPerm) {
                    CampaignRecipient::where('id', $row->campaign_recipient_id)
                        ->update(['status' => 'failed', 'error_message' => $error, 'sent_at' => now()]);
                    $this->updateHistory($row->campaign_id, $row->recipient_email, 'failed', $error);
                }
            }
            Campaign::find($row->campaign_id)?->refreshCounts();
            $this->finalizeCampaign($row->campaign_id);
            $processed++;
        }
        return $processed;
    }

    private function updateHistory(int $campaignId, string $email, string $status, ?string $error): void
    {
        EmailHistory::where('campaign_id', $campaignId)
            ->where('recipient_email', $email)
            ->where('status', 'pending')
            ->latest('id')->first()
            ?->update(['status' => $status, 'sent_at' => now(), 'error_message' => $error]);
    }

    private function finalizeCampaign(int $campaignId): void
    {
        $pending = EmailQueue::where('campaign_id', $campaignId)
            ->whereIn('status', ['pending', 'processing'])->count();
        if ($pending > 0) {
            Campaign::where('id', $campaignId)->whereNotIn('status', ['cancelled'])->update(['status' => 'processing']);
            return;
        }
        $campaign = Campaign::find($campaignId);
        if (!$campaign) return;
        $newStatus = ($campaign->total_sent === 0 && $campaign->total_failed > 0) ? 'failed' : 'completed';
        Campaign::where('id', $campaignId)->whereIn('status', ['pending', 'processing'])->update(['status' => $newStatus]);
    }
}
