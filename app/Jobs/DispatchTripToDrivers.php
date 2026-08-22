<?php

namespace App\Jobs;

use App\Enums\DriverStatus;
use App\Enums\TripOfferStatus;
use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchTripToDrivers implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    private const RADIUS_KM = 5; // simulate as getting from a config file or settings

    private const MAX_DRIVERS = 5; // simulate as getting from a config file or settings

    private const EARTH_RADIUS_KM = 6371; // the average radius of the Earth in kilometers

    public function __construct(public int $tripId) {}

    public function handle(): void
    {
        Log::info('Dispatch job started', [
            'trip_id' => $this->tripId,
        ]);

        $trip = Trip::find($this->tripId);

        if (!$trip || $trip->status !== TripStatus::Searching) {
            return;
        }

        $drivers = $this->getNearbyAvailableDrivers($trip->pickup_latitude, $trip->pickup_longitude);

        if ($drivers->isEmpty()) {
            Log::info('Dispatch found no available drivers', [
                'trip_id'   => $trip->id,
                'radius_km' => self::RADIUS_KM,
            ]);

            return;
        }

        $this->createOffers($trip->id, $drivers);

        foreach ($drivers as $driver) {
            NotifyDriverOfOffer::dispatch($trip->id, (int) $driver->id);
        }

        Log::info('Trip offered to drivers', [
            'trip_id'    => $trip->id,
            'driver_ids' => $drivers->pluck('id')->all(),
        ]);
    }

    private function getNearbyAvailableDrivers(float $pickupLatitude, float $pickupLongitude)
    {
        $distance = sprintf(
            '(%d * acos(
                least(1, greatest(-1,
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                ))
            ))',
            self::EARTH_RADIUS_KM
        );

        return DB::table('drivers')
            ->select('id')
            ->selectRaw("$distance AS distance_km", [$pickupLatitude, $pickupLongitude, $pickupLatitude])
            ->where('status', DriverStatus::Available->value)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("$distance <= ?", [$pickupLatitude, $pickupLongitude, $pickupLatitude, self::RADIUS_KM])
            ->orderBy('distance_km')
            ->limit(self::MAX_DRIVERS)
            ->get();
    }

    private function createOffers(int $tripId, Collection $drivers): void
    {
        $offeredAt = now();

        DB::table('trip_driver_offers')->insert(
            $drivers->map(fn ($driver) => [
                'trip_id'    => $tripId,
                'driver_id'  => $driver->id,
                'status'     => TripOfferStatus::Pending->value,
                'offered_at' => $offeredAt,
                'created_at' => $offeredAt,
                'updated_at' => $offeredAt,
            ])->all()
        );
    }
}
