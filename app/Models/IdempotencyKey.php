<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class IdempotencyKey extends Model
{
    use Prunable;

    protected $fillable = [
        'key',
        'status_code',
        'response_body',
        'response_headers',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'response_headers' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function prunable()
    {
        return static::where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
