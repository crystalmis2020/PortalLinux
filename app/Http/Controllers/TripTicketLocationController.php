<?php

namespace App\Http\Controllers;

use App\Models\TripTicketLocation;
use App\Services\DistanceCalculationService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripTicketLocationController extends Controller
{
    public function regions(): JsonResponse
    {
        return response()->json(
            TripTicketLocation::query()
                ->where('active', true)
                ->select('region_code as code', 'region_name as name')
                ->distinct()
                ->orderBy('region_name')
                ->get()
        );
    }

    public function provinces(Request $request): JsonResponse
    {
        $region = $request->string('region')->toString();

        return response()->json(
            TripTicketLocation::query()
                ->where('active', true)
                ->where('region_name', $region)
                ->select('province_code as code', 'province_name as name')
                ->distinct()
                ->orderBy('province_name')
                ->get()
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $region = $request->string('region')->toString();
        $province = $request->string('province')->toString();

        $columns = [
            'id',
            'city_municipality_code as code',
            'city_municipality_name as name',
            'destination',
        ];

        if (Schema::hasColumn('trip_ticket_locations', 'distance_from_maramag_km')) {
            $columns[] = 'distance_from_maramag_km as distance_km';
        }

        return response()->json(
            TripTicketLocation::query()
                ->where('active', true)
                ->where('region_name', $region)
                ->where('province_name', $province)
                ->select($columns)
                ->orderBy('city_municipality_name')
                ->get()
        );
    }

    public function distance(TripTicketLocation $tripTicketLocation, DistanceCalculationService $calculator): JsonResponse
    {
        abort_unless($tripTicketLocation->active, 404);

        return response()->json([
            'id' => $tripTicketLocation->id,
            'destination' => $tripTicketLocation->destination,
            'origin' => DistanceCalculationService::DEFAULT_ORIGIN_ADDRESS,
            'distance_km' => $calculator->distanceForLocation($tripTicketLocation),
            'distance_type' => 'road',
            'provider' => 'OSRM',
        ]);
    }
}
