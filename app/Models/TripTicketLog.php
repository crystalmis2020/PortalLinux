<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripTicketLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_ticket_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tripTicket(): BelongsTo
    {
        return $this->belongsTo(TripTicket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
