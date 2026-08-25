<?php

namespace Tests\Feature\Driver;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class NearbyDriversTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGIN_LAT = 24.4539;

    private const ORIGIN_LNG = 54.3773;

    private function nearby(array $params = []): TestResponse
    {
        return $this->getJson('/api/v1/drivers/nearby?' . http_build_query([
            'latitude'  => self::ORIGIN_LAT,
            'longitude' => self::ORIGIN_LNG,
            ...$params,
        ]));
    }

    public function test_orders_drivers_by_distance(): void
    {
        $far = Driver::factory()->create([
            'status'    => DriverStatus::Available,
            'latitude'  => self::ORIGIN_LAT + 0.02,
            'longitude' => self::ORIGIN_LNG,
        ]);
        $near = Driver::factory()->create([
            'status'    => DriverStatus::Available,
            'latitude'  => self::ORIGIN_LAT + 0.001,
            'longitude' => self::ORIGIN_LNG,
        ]);

        $response = $this->nearby();

        $response->assertOk();
        $this->assertSame([$near->id, $far->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_formats_distance_under_1km_as_meters(): void
    {
        Driver::factory()->create([
            'status'    => DriverStatus::Available,
            'latitude'  => self::ORIGIN_LAT + 0.0005, // ~56 m
            'longitude' => self::ORIGIN_LNG,
        ]);

        $response = $this->nearby();

        $response->assertOk();
        $this->assertMatchesRegularExpression('/^\d+ m$/', $response->json('data.0.distance'));
    }

    public function test_formats_distance_at_or_above_1km_as_kilometers(): void
    {
        Driver::factory()->create([
            'status'    => DriverStatus::Available,
            'latitude'  => self::ORIGIN_LAT + 0.05, // ~5.5 km
            'longitude' => self::ORIGIN_LNG,
        ]);

        $response = $this->nearby(['radius_km' => 10]);

        $response->assertOk();
        $this->assertMatchesRegularExpression('/^\d+\.\d{2} km$/', $response->json('data.0.distance'));
    }

    public function test_excludes_drivers_outside_the_radius(): void
    {
        Driver::factory()->create([
            'status'    => DriverStatus::Available,
            'latitude'  => self::ORIGIN_LAT + 1, // ~111 km
            'longitude' => self::ORIGIN_LNG,
        ]);

        $response = $this->nearby(['radius_km' => 5]);

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_excludes_busy_and_offline_drivers(): void
    {
        Driver::factory()->create([
            'status'    => DriverStatus::Busy,
            'latitude'  => self::ORIGIN_LAT,
            'longitude' => self::ORIGIN_LNG,
        ]);
        Driver::factory()->create([
            'status'    => DriverStatus::Offline,
            'latitude'  => self::ORIGIN_LAT,
            'longitude' => self::ORIGIN_LNG,
        ]);

        $response = $this->nearby();

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_excludes_drivers_without_a_location(): void
    {
        Driver::factory()->create([
            'status'    => DriverStatus::Available,
            'latitude'  => null,
            'longitude' => null,
        ]);

        $response = $this->nearby();

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_respects_the_limit_parameter(): void
    {
        Driver::factory()->count(3)->create([
            'status'    => DriverStatus::Available,
            'latitude'  => self::ORIGIN_LAT,
            'longitude' => self::ORIGIN_LNG,
        ]);

        $response = $this->nearby(['limit' => 2]);

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_rejects_missing_coordinates(): void
    {
        $response = $this->getJson('/api/v1/drivers/nearby');

        $response->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_rejects_radius_km_above_the_max(): void
    {
        $response = $this->nearby(['radius_km' => 51]);

        $response->assertUnprocessable()->assertJsonValidationErrors('radius_km');
    }
}
