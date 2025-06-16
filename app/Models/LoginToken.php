<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public static function generateFor($email)
    {
        return self::create([
            'email' => $email,
            'token' => \Str::random(32),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function isValid()
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isUsed()
    {
        return $this->used_at !== null;
    }

    public function markAsUsed()
    {
        $this->update([
            'used_at' => now(),
        ]);
    }
}
