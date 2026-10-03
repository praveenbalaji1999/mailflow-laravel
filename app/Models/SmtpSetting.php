<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    protected $table = 'smtp_settings';

    protected $fillable = ['host', 'port', 'encryption', 'username', 'password', 'from_name', 'from_email'];

    protected $hidden = [];

    public static function getSettings(): ?self
    {
        return static::orderBy('id')->first();
    }
}
