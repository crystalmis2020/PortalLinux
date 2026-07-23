<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'departure_odometer',
        'actual_return_datetime',
        'return_odometer',
        'departure_recorded_by',
        'departure_recorded_at',
        'return_recorded_by',
        'return_recorded_at',
        'gatekeeper_departure_remarks',
        'gatekeeper_return_remarks',
        'qr_token',
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
        'departure_odometer' => 'float',
        'actual_return_datetime' => 'datetime',
        'return_odometer' => 'float',
        'departure_recorded_at' => 'datetime',
        'return_recorded_at' => 'datetime',
        'encoded_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TripTicket $tripTicket): void {
            if (!$tripTicket->qr_token) {
                $tripTicket->qr_token = self::generateQrToken();
            }
        });
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::lower(Str::random(20));
        } while (self::where('qr_token', $token)->exists());

        return $token;
    }

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

    public function departureRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'departure_recorded_by');
    }

    public function returnRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_recorded_by');
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
