<?php

namespace Tests\Feature\Trip;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_trip_with_its_customer_and_driver(): void
    {
        $customer = Customer::factory()->create(['name' => 'Jane Customer']);
        $driver = Driver::factory()->create(['name' => 'Joe Driver']);
        $trip = Trip::factory()->create([
            'customer_id' => $customer->id,
            'driver_id'   => $driver->id,
        ]);

        $response = $this->getJson("/api/v1/trips/{$trip->id}");

        $response->assertOk();
        $this->assertSame($trip->id, $response->json('data.id'));
        $this->assertSame($customer->id, $response->json('data.customer.id'));
        $this->assertSame('Jane Customer', $response->json('data.customer.name'));
        $this->assertSame($driver->id, $response->json('data.driver.id'));
        $this->assertSame('Joe Driver', $response->json('data.driver.name'));
    }

    public function test_driver_fields_are_null_when_no_driver_is_assigned(): void
    {
        $trip = Trip::factory()->create(['driver_id' => null]);

        $response = $this->getJson("/api/v1/trips/{$trip->id}");

        $response->assertOk();
        $this->assertNull($response->json('data.driver.id'));
        $this->assertNull($response->json('data.driver.name'));
    }

    public function test_returns_404_for_an_unknown_trip(): void
    {
        $response = $this->getJson('/api/v1/trips/999999');

        $response->assertNotFound();
    }
}
