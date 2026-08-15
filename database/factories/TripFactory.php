<?php

namespace Database\Factories;

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
            'customer_id' => Customer::factory(),
            'driver_id'   => Driver::factory(),
            'latitude'    => $this->faker->latitude(),
            'longitude'   => $this->faker->longitude(),
            'type'        => $this->faker->randomElement(['towing', 'repair']),
            'status'      => $this->faker->randomElement(TripStatus::cases()),
        ];
    }
}
