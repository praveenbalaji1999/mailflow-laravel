<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailQueue extends Model
{
    protected $table = 'email_queue';

    protected $fillable = [
        'campaign_id', 'campaign_recipient_id', 'recipient_email',
        'subject', 'message', 'attachment_path', 'status',
        'attempts', 'last_error', 'scheduled_at', 'processed_at',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignRecipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class);
    }
}
