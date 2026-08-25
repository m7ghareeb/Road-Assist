<?php

namespace Tests\Feature\Trip;

use App\Enums\DriverStatus;
use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTripStatusTest extends TestCase
{
    use RefreshDatabase;

    private function tripAssignedTo(Driver $driver, TripStatus $status = TripStatus::Assigned): Trip
    {
        return Trip::factory()->create(['status' => $status, 'driver_id' => $driver->id]);
    }

    public function test_moves_from_assigned_to_arrived(): void
    {
        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = $this->tripAssignedTo($driver, TripStatus::Assigned);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'driver_id' => $driver->id,
            'status'    => TripStatus::Arrived->value,
        ]);

        $response->assertOk();
        $this->assertSame('arrived', $response->json('data.status'));
    }

    public function test_moves_from_arrived_to_started(): void
    {
        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = $this->tripAssignedTo($driver, TripStatus::Arrived);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'driver_id' => $driver->id,
            'status'    => TripStatus::Started->value,
        ]);

        $response->assertOk();
        $this->assertSame('started', $response->json('data.status'));
    }

    public function test_moving_to_completed_frees_the_driver(): void
    {
        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = $this->tripAssignedTo($driver, TripStatus::Started);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'driver_id' => $driver->id,
            'status'    => TripStatus::Completed->value,
        ]);

        $response->assertOk();
        $this->assertSame('completed', $response->json('data.status'));
        $this->assertSame(DriverStatus::Available, $driver->fresh()->status);
    }

    public function test_rejects_an_out_of_order_transition(): void
    {
        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = $this->tripAssignedTo($driver, TripStatus::Assigned);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'driver_id' => $driver->id,
            'status'    => TripStatus::Completed->value,
        ]);

        $response->assertUnprocessable();
        $this->assertSame('assigned', $trip->fresh()->status->value);
    }

    public function test_returns_403_when_the_driver_id_does_not_match_the_trip(): void
    {
        $assignedDriver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $otherDriver = Driver::factory()->create(['status' => DriverStatus::Available]);
        $trip = $this->tripAssignedTo($assignedDriver, TripStatus::Assigned);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'driver_id' => $otherDriver->id,
            'status'    => TripStatus::Arrived->value,
        ]);

        $response->assertForbidden();
    }

    public function test_rejects_a_missing_status(): void
    {
        $driver = Driver::factory()->create(['status' => DriverStatus::Busy]);
        $trip = $this->tripAssignedTo($driver, TripStatus::Assigned);

        $response = $this->patchJson("/api/v1/trips/{$trip->id}/status", [
            'driver_id' => $driver->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_returns_404_for_an_unknown_trip(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->patchJson('/api/v1/trips/999999/status', [
            'driver_id' => $driver->id,
            'status'    => TripStatus::Arrived->value,
        ]);

        $response->assertNotFound();
    }
}
