<?php

namespace App\Actions\Drivers;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Support\Collection;

final class FindNearbyDriversAction
{
    private const EARTH_RADIUS_KM = 6371; // the average radius of the Earth in kilometers

    public const DEFAULT_RADIUS_KM = 5; // simulate as getting from a config file or settings

    public const DEFAULT_LIMIT = 5; // simulate as getting from a config file or settings

    public static function handle(
        float $latitude,
        float $longitude,
        float $radiusKm = self::DEFAULT_RADIUS_KM,
        int $limit = self::DEFAULT_LIMIT,
    ): Collection {
        $distance = self::distanceExpression();

        return Driver::query()
            ->select('id', 'name', 'latitude', 'longitude', 'status')
            ->selectRaw("$distance AS distance", [$latitude, $longitude, $latitude])
            ->where('status', DriverStatus::Available->value)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("$distance <= ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    private static function distanceExpression(): string
    {
        return sprintf(
            '(%d * acos(
                least(1, greatest(-1,
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                ))
            ))',
            self::EARTH_RADIUS_KM
        );
    }
}
