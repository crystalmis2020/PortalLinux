<?php

namespace Database\Seeders;

use App\Models\TripTicketLocation;
use Illuminate\Database\Seeder;

class TripTicketLocationSeeder extends Seeder
{
    public function run(): void
    {
        collect(config('trip_ticket.location_rows', []))
            ->chunk(100)
            ->each(function ($rows): void {
                TripTicketLocation::upsert(
                    $rows->map(function (array $row): array {
                        return array_merge($row, [
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    })->all(),
                    ['city_municipality_code'],
                    [
                        'island_group_code',
                        'island_group_name',
                        'region_code',
                        'region_name',
                        'province_code',
                        'province_name',
                        'city_municipality_name',
                        'psgc_10_digit_code',
                        'destination',
                        'active',
                        'updated_at',
                    ]
                );
            });
    }
}
