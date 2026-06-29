<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class TripTicketLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'island_group_code',
        'island_group_name',
        'region_code',
        'region_name',
        'province_code',
        'province_name',
        'city_municipality_code',
        'city_municipality_name',
        'psgc_10_digit_code',
        'latitude',
        'longitude',
        'distance_from_maramag_km',
        'destination',
        'active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'distance_from_maramag_km' => 'float',
        'active' => 'boolean',
    ];

    public function tripTickets(): HasMany
    {
        return $this->hasMany(TripTicket::class, 'trip_ticket_location_id');
    }

    public static function locationTree(): array
    {
        if (! Schema::hasTable('trip_ticket_locations')) {
            return config('trip_ticket.locations', []);
        }

        $tree = [];

        static::query()
            ->where('active', true)
            ->orderBy('region_name')
            ->orderBy('province_name')
            ->orderBy('city_municipality_name')
            ->get(['id', 'region_name', 'province_name', 'city_municipality_name', 'destination', 'distance_from_maramag_km'])
            ->each(function (TripTicketLocation $location) use (&$tree): void {
                $tree[$location->region_name][$location->province_name][] = [
                    'id' => $location->id,
                    'name' => $location->city_municipality_name,
                    'destination' => $location->destination,
                    'distance_km' => $location->distance_from_maramag_km,
                ];
            });

        return $tree;
    }

    public static function destinations(): array
    {
        if (! Schema::hasTable('trip_ticket_locations')) {
            return config('trip_ticket.destinations', []);
        }

        return static::query()
            ->where('active', true)
            ->orderBy('destination')
            ->pluck('destination')
            ->all();
    }
}
