<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['name', 'description'];

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Recipient::class, 'group_members');
    }
}
