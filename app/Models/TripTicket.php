<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripTicket extends Model
{
    use HasFactory;

    public const STATUS_PENDING_DETAILS = 'pending_details';
    public const STATUS_FOR_APPROVAL = 'for_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ticket_number',
        'requested_by',
        'department_id',
        'section_id',
        'purpose',
        'destination',
        'trip_ticket_location_id',
        'distance_km',
        'requested_start_datetime',
        'requested_end_datetime',
        'passengers',
        'contact_number',
        'remarks',
        'vehicle_id',
        'vehicle_details',
        'driver_id',
        'driver_name',
        'actual_departure_datetime',
        'actual_return_datetime',
        'encoded_by',
        'encoded_at',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'status',
    ];

    protected $casts = [
        'distance_km' => 'float',
        'requested_start_datetime' => 'datetime',
        'requested_end_datetime' => 'datetime',
        'actual_departure_datetime' => 'datetime',
        'actual_return_datetime' => 'datetime',
        'encoded_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_DETAILS,
            self::STATUS_FOR_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_RETURNED,
            self::STATUS_DISPATCHED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(TripTicketLocation::class, 'trip_ticket_location_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TripTicketLog::class);
    }
}
