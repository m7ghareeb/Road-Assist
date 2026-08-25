<?php

namespace Tests\Feature\Concurrency;

use App\Enums\DriverStatus;
use App\Enums\TripOfferStatus;
use App\Enums\TripStatus;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripDriverOffer;
use App\Models\TripEvent;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AcceptTripConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Http::timeout(2)->get(config('app.url') . '/up');
        } catch (\Throwable) {
            $this->markTestSkipped(
                'Requires the test server: APP_ENV=testing php artisan serve --port=8001'
            );
        }
    }

    protected function tearDown(): void
    {
        // This test drives real concurrent requests through a separately running
        // server process, so DatabaseTruncation's per-test rows are genuinely
        // committed (not wrapped in a rolled-back transaction like RefreshDatabase
        // tests). Without this, they'd persist into whichever test runs next in
        // the suite. Deleting trips cascades to trip_driver_offers/trip_events.
        Trip::query()->delete();
        Driver::query()->delete();
        Customer::query()->delete();

        parent::tearDown();
    }

    public function test_only_one_driver_can_accept_trip_concurrently(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());

        $trip = Trip::factory()->create([
            'status'    => TripStatus::Searching->value,
            'driver_id' => null,
        ]);

        $drivers = Driver::factory()
            ->count(100)
            ->create([
                'status' => DriverStatus::Available->value,
            ]);

        TripDriverOffer::insert(
            $drivers->map(fn (Driver $driver) => [
                'trip_id'    => $trip->id,
                'driver_id'  => $driver->id,
                'status'     => TripOfferStatus::Pending->value,
                'offered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
        // Simulate 100 drivers concurrently attempting to accept the same trip.
        $responses = Http::pool(
            fn (Pool $pool) => $drivers
                ->map(fn (Driver $driver) => $pool
                    ->withoutVerifying()
                    ->acceptJson()
                    ->post(config('app.url') . "/api/v1/trips/{$trip->id}/accept", [
                        'driver_id' => $driver->id,
                    ]))
                ->all()
        );

        $codes = collect($responses)->map->status();

        // ── Response outcomes ──
        $this->assertSame(
            1,
            $codes->filter(fn ($c) => $c === 200)->count(),
            'Exactly one accept must succeed.'
        );

        $this->assertSame(
            99,
            $codes
                ->filter(fn ($c) => in_array($c, [403, 409]))
                ->count(),
            'Every other attempt must be rejected.'
        );

        // ── Resulting database state ──
        $trip->refresh();

        $this->assertSame(TripStatus::Assigned, $trip->status);
        $this->assertNotNull($trip->driver_id);

        $this->assertSame(1, TripDriverOffer::where('trip_id', $trip->id)
            ->where('status', TripOfferStatus::Accepted)->count());

        $this->assertSame(99, TripDriverOffer::where('trip_id', $trip->id)
            ->where('status', TripOfferStatus::Closed)->count());

        $this->assertSame(1, Driver::where('status', DriverStatus::Busy)->count());

        // Independent check — two winning transactions would leave two events.
        $this->assertSame(1, TripEvent::where('trip_id', $trip->id)
            ->where('to_status', TripStatus::Assigned->value)->count());

        // The winner's own offer must be the accepted one.
        $this->assertSame(TripOfferStatus::Accepted, TripDriverOffer::query()
            ->where('trip_id', $trip->id)
            ->where('driver_id', $trip->driver_id)
            ->value('status'));
    }
}
