<?php

namespace App\Actions\Drivers;

use App\Models\Driver;

final class UpdateDriverLocationAction
{
    public static function handle(Driver $driver, float $latitude, float $longitude): Driver
    {
        Driver::query()
            ->where('id', $driver->id)
            ->update([
                'latitude'   => $latitude,
                'longitude'  => $longitude,
                'updated_at' => now(),
            ]);

        return $driver->fresh();
    }
}
