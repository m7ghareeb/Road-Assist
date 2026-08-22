<?php

namespace App\Actions\Trips;

use App\Enums\TripStatus;
use App\Jobs\DispatchTripToDrivers;
use App\Models\Customer;
use App\Models\Trip;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CreateTrip
{
    public static function handle(array $data): Trip
    {
        try {
            $trip = DB::transaction(function () use ($data) {
                $trip = Trip::create([
                    'status'           => TripStatus::Searching->value,
                    'type'             => $data['type'],
                    'pickup_latitude'  => $data['pickup_latitude'],
                    'pickup_longitude' => $data['pickup_longitude'],
                    'customer_id'      => $data['customer_id'], // simulate as a auth user
                    'idempotency_key'  => $data['idempotency_key'], // simulate as a unique key for idempotency
                ]);

                $trip->events()->create([
                    'from_status' => null,
                    'to_status'   => TripStatus::Searching->value,
                    'actor_type'  => Customer::class,
                    'actor_id'    => $trip->customer_id,
                ]);

                return $trip;
            });
        } catch (UniqueConstraintViolationException) {
            return Trip::query()
                ->where('customer_id', $data['customer_id'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->firstOrFail();
        }

        DispatchTripToDrivers::dispatch($trip->id);

        return $trip;
    }
}
