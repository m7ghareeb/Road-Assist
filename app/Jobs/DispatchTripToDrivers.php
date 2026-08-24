<?php

namespace App\Jobs;

use App\Actions\Drivers\FindNearbyDriversAction;
use App\Enums\DriverStatus;
use App\Enums\TripOfferStatus;
use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DispatchTripToDrivers implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public int $tripId) {}

    public function handle(): void
    {
        $trip = Trip::find($this->tripId);

        if (!$trip || $trip->status !== TripStatus::Searching) {
            return;
        }

        $drivers = FindNearbyDriversAction::handle($trip->pickup_latitude, $trip->pickup_longitude);

        if ($drivers->isEmpty()) {
            return;
        }

        $this->createOffers($trip->id, $drivers);

        foreach ($drivers as $driver) {
            NotifyDriverOfOffer::dispatch($trip->id, (int) $driver->id);
        }
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
