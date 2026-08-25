<?php

namespace Tests\Feature\Trip;

use App\Enums\DriverStatus;
use App\Enums\TripOfferStatus;
use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripDriverOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_assigns_the_trip_and_closes_rival_offers(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Searching, 'driver_id' => null]);
        $winner = Driver::factory()->create(['status' => DriverStatus::Available]);
        $rival = Driver::factory()->create(['status' => DriverStatus::Available]);

        TripDriverOffer::create([
            'trip_id' => $trip->id, 'driver_id' => $winner->id,
            'status'  => TripOfferStatus::Pending, 'offered_at' => now(),
        ]);
        TripDriverOffer::create([
            'trip_id' => $trip->id, 'driver_id' => $rival->id,
            'status'  => TripOfferStatus::Pending, 'offered_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/trips/{$trip->id}/accept", ['driver_id' => $winner->id]);

        $response->assertOk();
        $this->assertSame('assigned', $response->json('data.status'));

        $trip->refresh();
        $this->assertSame($winner->id, $trip->driver_id);
        $this->assertSame(TripStatus::Assigned, $trip->status);
        $this->assertSame(DriverStatus::Busy, $winner->fresh()->status);
        $this->assertSame(
            TripOfferStatus::Accepted,
            TripDriverOffer::where('trip_id', $trip->id)->where('driver_id', $winner->id)->value('status'),
        );
        $this->assertSame(
            TripOfferStatus::Closed,
            TripDriverOffer::where('trip_id', $trip->id)->where('driver_id', $rival->id)->value('status'),
        );
    }

    public function test_returns_403_when_the_driver_has_no_pending_offer(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Searching, 'driver_id' => null]);
        $driver = Driver::factory()->create(['status' => DriverStatus::Available]);

        $response = $this->postJson("/api/v1/trips/{$trip->id}/accept", ['driver_id' => $driver->id]);

        $response->assertForbidden();
    }

    public function test_returns_409_when_the_trip_is_already_assigned(): void
    {
        $winner = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $loser = Driver::factory()->create(['status' => DriverStatus::Available]);
        $trip = Trip::factory()->create(['status' => TripStatus::Assigned, 'driver_id' => $winner->id]);

        TripDriverOffer::create([
            'trip_id' => $trip->id, 'driver_id' => $loser->id,
            'status'  => TripOfferStatus::Pending, 'offered_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/trips/{$trip->id}/accept", ['driver_id' => $loser->id]);

        $response->assertStatus(409);
    }

    public function test_rejects_a_missing_driver_id(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Searching, 'driver_id' => null]);

        $response = $this->postJson("/api/v1/trips/{$trip->id}/accept", []);

        $response->assertUnprocessable()->assertJsonValidationErrors('driver_id');
    }

    public function test_returns_404_for_an_unknown_trip(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->postJson('/api/v1/trips/999999/accept', ['driver_id' => $driver->id]);

        $response->assertNotFound();
    }
}
