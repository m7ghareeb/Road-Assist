<?php

namespace App\Actions\Trips;

use App\Enums\DriverStatus;
use App\Enums\TripOfferStatus;
use App\Enums\TripStatus;
use App\Exceptions\DriverNotEligibleToAcceptTripException;
use App\Exceptions\TripAlreadyAssignedException;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripDriverOffer;
use Illuminate\Support\Facades\DB;

final class AcceptTripAction
{
    public static function handle(Trip $trip, Driver $driver): Trip
    {
        $hasOffer = TripDriverOffer::query()
            ->where('trip_id', $trip->id)
            ->where('driver_id', $driver->id)
            ->where('status', TripOfferStatus::Pending->value)
            ->exists();

        throw_unless($hasOffer, new DriverNotEligibleToAcceptTripException);

        $trip = DB::transaction(function () use ($trip, $driver) {

            $affectedRows = Trip::query()
                ->where('id', $trip->id)
                ->where('status', TripStatus::Searching->value)
                ->whereNull('driver_id')
                ->update([
                    'driver_id'  => $driver->id,
                    'status'     => TripStatus::Assigned->value,
                    'updated_at' => now(),
                ]);

            throw_if($affectedRows === 0, new TripAlreadyAssignedException);

            TripDriverOffer::query()
                ->where('trip_id', $trip->id)
                ->where('driver_id', $driver->id)
                ->update(['status' => TripOfferStatus::Accepted->value]);

            TripDriverOffer::query()
                ->where('trip_id', $trip->id)
                ->where('driver_id', '!=', $driver->id)
                ->where('status', TripOfferStatus::Pending->value)
                ->update(['status' => TripOfferStatus::Closed->value]);

            Driver::query()
                ->where('id', $driver->id)
                ->update(['status' => DriverStatus::Busy->value]);

            $trip->events()->create([
                'from_status' => TripStatus::Searching->value,
                'to_status'   => TripStatus::Assigned->value,
                'actor_type'  => Driver::class,
                'actor_id'    => $driver->id,
            ]);

            return $trip->fresh();
        });

        return $trip;
    }
}
