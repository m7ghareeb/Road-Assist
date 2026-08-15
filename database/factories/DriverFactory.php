<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => $this->faker->name(),
            'latitude'  => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'status'    => $this->faker->randomElement(DriverStatus::cases()),
        ];
    }
}
