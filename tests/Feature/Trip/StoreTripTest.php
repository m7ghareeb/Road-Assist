<?php

namespace Tests\Feature\Trip;

use App\Enums\TowType;
use App\Jobs\DispatchTripToDrivers;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreTripTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'customer_id'      => $customer->id,
            'pickup_latitude'  => 24.4539,
            'pickup_longitude' => 54.3773,
            'type'             => TowType::Normal->value,
        ], $overrides);
    }

    public function test_creates_a_trip_and_dispatches_driver_search(): void
    {
        Bus::fake();
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/trips', $this->payload($customer), [
            'idempotency-key' => Str::uuid()->toString(),
        ]);

        $response->assertCreated();
        $this->assertSame('searching', $response->json('data.status'));
        $this->assertDatabaseCount('trips', 1);
        Bus::assertDispatchedTimes(DispatchTripToDrivers::class, 1);
    }

    public function test_replaying_the_same_idempotency_key_returns_200_without_redispatching(): void
    {
        Bus::fake();
        $customer = Customer::factory()->create();
        $key = Str::uuid()->toString();

        $first = $this->postJson('/api/v1/trips', $this->payload($customer), ['idempotency-key' => $key]);
        $second = $this->postJson('/api/v1/trips', $this->payload($customer), ['idempotency-key' => $key]);

        $first->assertCreated();
        $second->assertOk();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('trips', 1);
        Bus::assertDispatchedTimes(DispatchTripToDrivers::class, 1);
    }

    public function test_rejects_a_missing_idempotency_key_header(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/trips', $this->payload($customer));

        $response->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
    }

    public function test_rejects_an_unknown_customer_id(): void
    {
        $response = $this->postJson('/api/v1/trips', [
            'customer_id'      => 999999,
            'pickup_latitude'  => 24.4539,
            'pickup_longitude' => 54.3773,
            'type'             => TowType::Normal->value,
        ], ['idempotency-key' => Str::uuid()->toString()]);

        $response->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_rejects_an_invalid_tow_type(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/trips', $this->payload($customer, ['type' => 'helicopter']), [
            'idempotency-key' => Str::uuid()->toString(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_rejects_an_out_of_range_pickup_latitude(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->postJson('/api/v1/trips', $this->payload($customer, ['pickup_latitude' => 200]), [
            'idempotency-key' => Str::uuid()->toString(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('pickup_latitude');
    }
}
