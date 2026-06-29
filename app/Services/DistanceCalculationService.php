<?php

namespace App\Services;

use App\Models\TripTicketLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class DistanceCalculationService
{
    public const DEFAULT_ORIGIN_ADDRESS = 'Maramag, Bukidnon, Philippines';
    public const DEFAULT_ORIGIN_LATITUDE = 7.7610686;
    public const DEFAULT_ORIGIN_LONGITUDE = 125.0051580;

    public function calculate(
        string $destinationAddress,
        ?string $originAddress = null,
        ?array $destinationCoordinates = null,
        ?array $originCoordinates = null
    ): ?float {
        $originCoordinates ??= $this->coordinatesForAddress($originAddress ?: self::DEFAULT_ORIGIN_ADDRESS, [
            'latitude' => self::DEFAULT_ORIGIN_LATITUDE,
            'longitude' => self::DEFAULT_ORIGIN_LONGITUDE,
        ]);

        $destinationCoordinates ??= $this->coordinatesForAddress($destinationAddress);

        if (! $originCoordinates || ! $destinationCoordinates) {
            return null;
        }

        return $this->roadDistanceKm($originCoordinates, $destinationCoordinates);
    }

    public function distanceForLocation(TripTicketLocation $location, ?string $originAddress = null): ?float
    {
        if (! Schema::hasColumn('trip_ticket_locations', 'distance_from_maramag_km')) {
            return null;
        }

        if ($location->distance_from_maramag_km !== null) {
            return (float) $location->distance_from_maramag_km;
        }

        $destinationCoordinates = $this->coordinatesForLocation($location);

        if (! $destinationCoordinates) {
            return null;
        }

        $distance = $this->calculate(
            destinationAddress: $location->destination . ', Philippines',
            originAddress: $originAddress,
            destinationCoordinates: $destinationCoordinates
        );

        if ($distance === null) {
            return null;
        }

        $location->forceFill([
            'latitude' => $destinationCoordinates['latitude'],
            'longitude' => $destinationCoordinates['longitude'],
            'distance_from_maramag_km' => $distance,
        ])->save();

        return $distance;
    }

    protected function coordinatesForLocation(TripTicketLocation $location): ?array
    {
        if ($location->latitude !== null && $location->longitude !== null) {
            return [
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
            ];
        }

        return $this->coordinatesForAddress($location->destination . ', Philippines');
    }

    protected function coordinatesForAddress(string $address, ?array $fallback = null): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'SupportPortalTripTicket/1.0',
        ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'limit' => 1,
            'q' => $address,
        ]);

        if (! $response->successful()) {
            return $fallback;
        }

        $result = $response->json('0');

        if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
            return $fallback;
        }

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
        ];
    }

    protected function roadDistanceKm(array $originCoordinates, array $destinationCoordinates): ?float
    {
        $coordinates = sprintf(
            '%F,%F;%F,%F',
            $originCoordinates['longitude'],
            $originCoordinates['latitude'],
            $destinationCoordinates['longitude'],
            $destinationCoordinates['latitude']
        );

        $response = Http::timeout(10)->get("https://router.project-osrm.org/route/v1/driving/{$coordinates}", [
            'overview' => 'false',
            'alternatives' => 'false',
            'steps' => 'false',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $distanceMeters = $response->json('routes.0.distance');

        if (! is_numeric($distanceMeters)) {
            return null;
        }

        return round(((float) $distanceMeters) / 1000, 2);
    }
}
