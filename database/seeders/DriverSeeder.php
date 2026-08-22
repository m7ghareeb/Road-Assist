<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Dispatch test drivers positioned around the pickup point
     * at latitude 24.4539, longitude 54.3773.
     */
    public function run(): void
    {
        $drivers = [
            // available, ~0.15 km from pickup
            [
                'name'      => 'Driver 1',
                'latitude'  => 24.4550000,
                'longitude' => 54.3780000,
                'status'    => DriverStatus::Available,
            ],
            // available, ~2.1 km from pickup
            [
                'name'      => 'Driver 2',
                'latitude'  => 24.4700000,
                'longitude' => 54.3900000,
                'status'    => DriverStatus::Available,
            ],
            // available, ~8 km from pickup
            [
                'name'      => 'Driver 3',
                'latitude'  => 24.5200000,
                'longitude' => 54.4200000,
                'status'    => DriverStatus::Available,
            ],
            // busy, nearby
            [
                'name'      => 'Driver 4',
                'latitude'  => 24.4545000,
                'longitude' => 54.3775000,
                'status'    => DriverStatus::Busy,
            ],
            // available, no location
            [
                'name'      => 'Driver 5',
                'latitude'  => null,
                'longitude' => null,
                'status'    => DriverStatus::Available,
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::query()->updateOrCreate(
                ['name' => $driver['name']],
                $driver,
            );
        }
    }
}
