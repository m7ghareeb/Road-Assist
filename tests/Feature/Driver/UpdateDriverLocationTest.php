<?php

namespace Tests\Feature\Driver;

use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateDriverLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_the_drivers_location(): void
    {
        $driver = Driver::factory()->create([
            'latitude'  => 24.0,
            'longitude' => 54.0,
        ]);

        $response = $this->postJson("/api/v1/drivers/{$driver->id}/location", [
            'latitude'  => 24.5,
            'longitude' => 54.5,
        ]);

        $response->assertOk();

        $driver->refresh();
        $this->assertSame(24.5, $driver->latitude);
        $this->assertSame(54.5, $driver->longitude);
    }

    public function test_omits_the_distance_field_from_the_response(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->postJson("/api/v1/drivers/{$driver->id}/location", [
            'latitude'  => 24.5,
            'longitude' => 54.5,
        ]);

        $response->assertOk();
        $this->assertArrayNotHasKey('distance', $response->json('data'));
    }

    public function test_returns_404_for_an_unknown_driver(): void
    {
        $response = $this->postJson('/api/v1/drivers/999999/location', [
            'latitude'  => 24.5,
            'longitude' => 54.5,
        ]);

        $response->assertNotFound();
    }

    public function test_rejects_a_missing_latitude(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->postJson("/api/v1/drivers/{$driver->id}/location", [
            'longitude' => 54.5,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('latitude');
    }

    public function test_rejects_an_out_of_range_latitude(): void
    {
        $driver = Driver::factory()->create();

        $response = $this->postJson("/api/v1/drivers/{$driver->id}/location", [
            'latitude'  => 200,
            'longitude' => 54.5,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('latitude');
    }
}
