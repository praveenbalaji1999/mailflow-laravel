<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipient extends Model
{
    protected $fillable = ['name', 'email', 'status'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members');
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name), 2) ?: ['U'];
        return strtoupper(implode('', array_map(fn($p) => substr($p, 0, 1), $parts)));
    }
}
