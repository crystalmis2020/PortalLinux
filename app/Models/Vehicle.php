<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'plate_number',
        'is_available',
    ];

    /**
     * Get the sections associated with the trip_ticekt.
     */
    public function tripTickets(): HasMany{
        return $this->hasMany(TripTicket::class);
    }
}
