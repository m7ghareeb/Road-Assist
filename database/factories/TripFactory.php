<?php

namespace Database\Factories;

use App\Enums\TowType;
use App\Enums\TripStatus;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id'      => Customer::factory(),
            'driver_id'        => Driver::factory(),
            'pickup_latitude'  => $this->faker->latitude(),
            'pickup_longitude' => $this->faker->longitude(),
            'type'             => $this->faker->randomElement(TowType::cases()),
            'status'           => $this->faker->randomElement(TripStatus::cases()),
        ];
    }
}
