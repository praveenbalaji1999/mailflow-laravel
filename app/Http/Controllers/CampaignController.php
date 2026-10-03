<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailHistory;
use App\Models\EmailQueue;
use App\Models\Group;
use App\Models\Recipient;
use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::latest()->paginate(10);

        return view('campaigns.index', compact('campaigns'));
    }

    public function compose(Request $request)
    {
        $campaign   = null;
        $selectedIds = [];

        if ($editId = (int) $request->get('id')) {
            $campaign = Campaign::where('id', $editId)->where('status', 'draft')->first();
            if ($campaign) {
                $selectedIds = CampaignRecipient::where('campaign_id', $editId)
                    ->whereNotNull('recipient_id')
                    ->pluck('recipient_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();
            }
        }

        $groups     = Group::orderBy('name')->get();
        $recipients = Recipient::where('status', 'active')->orderBy('name')->limit(200)->get();
        $smtp       = SmtpSetting::getSettings();

        return view('campaigns.compose', compact('campaign', 'selectedIds', 'groups', 'recipients', 'smtp'));
    }

    public function store(Request $request)
    {
        $action     = $request->input('action', 'draft');
        $campaignId = (int) $request->input('campaign_id', 0);

        $request->validate([
            'campaign_name' => 'required|string|max:200',
            'subject'       => $action === 'send' ? 'required|string|max:255' : 'nullable|string|max:255',
            'message'       => $action === 'send' ? 'required|string' : 'nullable|string',
        ]);

        $recipientIds = array_values(array_unique(array_map('intval', (array) $request->input('recipients', []))));
        $groupId      = (int) $request->input('group_id', 0);

        if ($groupId > 0) {
            $groupRecipients = Recipient::whereHas('groups', fn($q) => $q->where('groups.id', $groupId))
                ->where('status', 'active')
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            $recipientIds = array_values(array_unique(array_merge($recipientIds, $groupRecipients)));
        }

        if ($action === 'send') {
            if (count($recipientIds) === 0) {
                return back()->withInput()->with('error', 'Please select at least one recipient.');
            }
            if (!SmtpSetting::getSettings()) {
                return back()->withInput()->with('error', 'Configure SMTP settings before sending.');
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $filename       = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/public/attachments'), $filename);
            $attachmentPath = 'storage/attachments/' . $filename;
        } elseif ($request->input('existing_attachment')) {
            $attachmentPath = $request->input('existing_attachment');
        }

        DB::transaction(function () use (
            $request, $action, &$campaignId, $recipientIds,
            $attachmentPath
        ) {
            $status = $action === 'send' ? 'pending' : 'draft';
            $adminId = session('admin_id');

            if ($campaignId > 0) {
                $existing = Campaign::find($campaignId);
                if ($existing && in_array($existing->status, ['draft', 'pending'])) {
                    if (!$attachmentPath) {
                        $attachmentPath = $existing->attachment_path;
                    }
                    EmailQueue::where('campaign_id', $campaignId)->delete();
                    CampaignRecipient::where('campaign_id', $campaignId)->delete();
                    $existing->update([
                        'campaign_name'   => $request->input('campaign_name'),
                        'subject'         => $request->input('subject', ''),
                        'message'         => $request->input('message', ''),
                        'attachment_path' => $attachmentPath,
                        'status'          => $status,
                        'created_by'      => $adminId,
                    ]);
                }
            } else {
                $campaign = Campaign::create([
                    'campaign_name'   => $request->input('campaign_name'),
                    'subject'         => $request->input('subject', ''),
                    'message'         => $request->input('message', ''),
                    'attachment_path' => $attachmentPath,
                    'status'          => $status,
                    'created_by'      => $adminId,
                ]);
                $campaignId = $campaign->id;
            }

            foreach ($recipientIds as $rid) {
                $rec = Recipient::where('id', $rid)->where('status', 'active')->first();
                if (!$rec) continue;

                $cr = CampaignRecipient::create([
                    'campaign_id'  => $campaignId,
                    'recipient_id' => $rec->id,
                    'email'        => $rec->email,
                    'status'       => 'pending',
                ]);

                if ($action === 'send') {
                    EmailQueue::create([
                        'campaign_id'           => $campaignId,
                        'campaign_recipient_id' => $cr->id,
                        'recipient_email'       => $rec->email,
                        'subject'               => $request->input('subject'),
                        'message'               => $request->input('message'),
                        'attachment_path'       => $attachmentPath,
                        'status'                => 'pending',
                        'scheduled_at'          => now(),
                    ]);

                    EmailHistory::create([
                        'campaign_id'      => $campaignId,
                        'recipient_id'     => $rec->id,
                        'recipient_email'  => $rec->email,
                        'subject'          => $request->input('subject'),
                        'status'           => 'pending',
                    ]);
                }
            }

            $campaign = Campaign::find($campaignId);
            $campaign?->refreshCounts();

            if ($action === 'send') {
                Campaign::where('id', $campaignId)->update(['status' => 'processing']);
            }
        });

        if ($action === 'send') {
            // Process first batch inline
            app(\App\Services\EmailQueueService::class)->processBatch(20, $campaignId);
            return redirect()->route('campaigns.show', $campaignId)->with('success', 'Campaign queued for sending.');
        }

        return redirect()->route('campaigns.compose', ['id' => $campaignId])->with('success', 'Draft saved.');
    }

    public function show(Campaign $campaign, Request $request)
    {
        $q     = trim((string) $request->get('q', ''));
        $query = CampaignRecipient::where('campaign_id', $campaign->id);
        if ($q !== '') {
            $query->where('email', 'like', "%{$q}%");
        }
        $deliveries  = $query->paginate(10)->withQueryString();
        $successRate = $campaign->total_recipients > 0
            ? round(($campaign->total_sent / $campaign->total_recipients) * 100, 1)
            : 0;

        return view('campaigns.show', compact('campaign', 'deliveries', 'successRate', 'q'));
    }

    public function processQueue(Campaign $campaign)
    {
        $n = app(\App\Services\EmailQueueService::class)->processBatch(20, $campaign->id);

        return back()->with('success', "Processed {$n} email(s).");
    }

    public function cancel(Campaign $campaign)
    {
        if (in_array($campaign->status, ['pending', 'processing'])) {
            $campaign->update(['status' => 'cancelled']);
            EmailQueue::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'last_error' => 'Cancelled']);
            $campaign->refreshCounts();
        }

        return back()->with('success', 'Campaign cancelled.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }
}
