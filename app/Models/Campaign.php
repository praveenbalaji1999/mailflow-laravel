<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'campaign_name', 'subject', 'message', 'attachment_path',
        'status', 'total_recipients', 'total_sent', 'total_failed',
        'total_pending', 'created_by',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function emailQueue(): HasMany
    {
        return $this->hasMany(EmailQueue::class);
    }

    public function emailHistory(): HasMany
    {
        return $this->hasMany(EmailHistory::class);
    }

    public function refreshCounts(): void
    {
        $counts = $this->campaignRecipients()
            ->selectRaw("COUNT(*) as total, SUM(status='sent') as sent, SUM(status='failed') as failed, SUM(status='pending') as pending")
            ->first();

        $this->update([
            'total_recipients' => (int) $counts->total,
            'total_sent'       => (int) $counts->sent,
            'total_failed'     => (int) $counts->failed,
            'total_pending'    => (int) $counts->pending,
        ]);
    }
}
