<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternetAccessConnectorToken extends Model
{
    protected $fillable = [
        'internet_access_request_id',
        'token_hash',
        'issued_ip',
        'consumed_ip',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function internetAccessRequest(): BelongsTo
    {
        return $this->belongsTo(InternetAccessRequest::class);
    }
}
