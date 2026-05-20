<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Get the sections associated with the department.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Get the sections associated with the trip_ticekt.
     */
    public function tripTickets(): HasMany{
        return $this->hasMany(TripTicket::class);
    }

    /**
     * Get the sections associated with the messhalls.
     */
    public function messhalls(): HasMany
    {
        return $this->hasMany(Messhall::class);
    }
}
