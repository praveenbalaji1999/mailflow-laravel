<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailHistory extends Model
{
    protected $table = 'email_history';

    public $timestamps = false;

    protected $fillable = [
        'campaign_id', 'recipient_id', 'recipient_email',
        'subject', 'status', 'sent_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'sent_at'    => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Recipient::class);
    }
}
