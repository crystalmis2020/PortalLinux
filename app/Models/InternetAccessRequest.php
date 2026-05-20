<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternetAccessRequest extends Model
{
    use HasFactory;

    public const STATUS_READY = 'ready';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'requester_ip',
        'purpose',
        'requested_hours',
        'duration_minutes',
        'username',
        'password',
        'mikrotik_profile',
        'mikrotik_reference_id',
        'status',
        'connected_at',
        'expires_at',
        'expired_at',
        'last_seen_online_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'last_seen_online_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingSecondsAttribute(): int
    {
        if (! $this->expires_at || $this->status !== self::STATUS_ACTIVE) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_READY, self::STATUS_ACTIVE], true);
    }
}
