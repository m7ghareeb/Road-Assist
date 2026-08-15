<?php

namespace Database\Factories;

use App\Enums\TripOfferStatus;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\TripDriverOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripDriverOffer>
 */
class TripDriverOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id'    => Trip::factory(),
            'driver_id'  => Driver::factory(),
            'status'     => TripOfferStatus::Pending->value,
            'offered_at' => now(),
        ];
    }
}
