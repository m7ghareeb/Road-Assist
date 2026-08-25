<?php

namespace Tests\Feature\Events;

use App\Actions\Trips\AcceptTripAction;
use App\Actions\Trips\UpdateTripStatusAction;
use App\Enums\DriverStatus;
use App\Enums\TripOfferStatus;
use App\Enums\TripStatus;
use App\Events\TripStatusUpdated;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripDriverOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TripBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_a_trip_broadcasts_the_assignment(): void
    {
        Event::fake([TripStatusUpdated::class]);

        $trip = Trip::factory()->create(['status' => TripStatus::Searching->value, 'driver_id' => null]);
        $driver = Driver::factory()->create(['status' => DriverStatus::Available]);

        TripDriverOffer::create([
            'trip_id'    => $trip->id,
            'driver_id'  => $driver->id,
            'status'     => TripOfferStatus::Pending->value,
            'offered_at' => now(),
        ]);

        $trip = AcceptTripAction::handle($trip, $driver);

        Event::assertDispatched(TripStatusUpdated::class, function (TripStatusUpdated $event) use ($trip, $driver) {
            $payload = $event->broadcastWith();

            return
                $payload['trip_id'] === $trip->id &&
                $payload['from_status'] === TripStatus::Searching->value &&
                $payload['to_status'] === TripStatus::Assigned->value &&
                $payload['driver']['id'] === $driver->id;
        });
    }

    public function test_each_status_transition_broadcasts(): void
    {
        Event::fake([TripStatusUpdated::class]);

        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = Trip::factory()->create([
            'status'    => TripStatus::Assigned,
            'driver_id' => $driver->id,
        ]);

        foreach ([TripStatus::Arrived, TripStatus::Started, TripStatus::Completed] as $status) {
            UpdateTripStatusAction::handle($trip->fresh(), $status);
        }

        Event::assertDispatchedTimes(TripStatusUpdated::class, 3);
    }

    public function test_no_broadcast_when_the_transition_is_rejected(): void
    {
        Event::fake([TripStatusUpdated::class]);

        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = Trip::factory()->create([
            'status'    => TripStatus::Assigned,
            'driver_id' => $driver->id,
        ]);

        try {
            UpdateTripStatusAction::handle($trip, TripStatus::Completed);
        } catch (\Throwable) {
        }

        Event::assertNotDispatched(TripStatusUpdated::class);
    }

    public function test_no_broadcast_when_the_race_is_lost(): void
    {
        Event::fake([TripStatusUpdated::class]);

        $winner = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $loser = Driver::factory()->create(['status' => DriverStatus::Available]);

        $trip = Trip::factory()->create([
            'status'    => TripStatus::Assigned,
            'driver_id' => $winner->id,
        ]);

        TripDriverOffer::create([
            'trip_id'    => $trip->id,
            'driver_id'  => $loser->id,
            'status'     => TripOfferStatus::Pending,
            'offered_at' => now(),
        ]);

        try {
            AcceptTripAction::handle($trip, $loser);
        } catch (\Throwable) {
        }

        Event::assertNotDispatched(TripStatusUpdated::class);
    }
}
