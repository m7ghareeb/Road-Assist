<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\TripEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripEvent>
 */
class TripEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id'     => Trip::factory(),
            'from_status' => $this->faker->optional()->randomElement(TripStatus::cases())?->value,
            'to_status'   => $this->faker->randomElement(TripStatus::cases())->value,
            'actor_type'  => $this->faker->randomElement(['customer', 'driver', 'system']),
            'actor_id'    => $this->faker->numberBetween(1, 1000),
        ];
    }
}
