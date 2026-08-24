<?php

namespace App\Actions\Trips;

use App\Enums\DriverStatus;
use App\Enums\TripStatus;
use App\Exceptions\InvalidTripTransitionException;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

final class UpdateTripStatusAction
{
    public static function handle(Trip $trip, TripStatus $to): Trip
    {
        $driver = $trip->driver;
        $from = $trip->status;

        throw_unless(self::isValidTransition($from, $to), new InvalidTripTransitionException($from, $to));

        return DB::transaction(function () use ($trip, $from, $to, $driver) {

            Trip::query()
                ->where('id', $trip->id)
                ->update([
                    'status'     => $to->value,
                    'updated_at' => now(),
                ]);

            $trip->events()->create([
                'from_status' => $from->value,
                'to_status'   => $to->value,
                'actor_type'  => Driver::class,
                'actor_id'    => $driver->id,
            ]);

            if ($to === TripStatus::Completed) {
                Driver::query()
                    ->where('id', $driver->id)
                    ->update(['status' => DriverStatus::Available->value]);
            }

            return $trip->fresh();
        });
    }

    private static function isValidTransition(TripStatus $from, TripStatus $to): bool
    {
        return match ($from) {
            TripStatus::Assigned => $to === TripStatus::Arrived,
            TripStatus::Arrived  => $to === TripStatus::Started,
            TripStatus::Started  => $to === TripStatus::Completed,
            default              => false,
        };
    }
}
