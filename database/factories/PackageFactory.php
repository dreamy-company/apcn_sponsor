<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Diamond', 'Platinum', 'White Gold', 'Gold', 'Silver']),
            'default_price' => fake()->numberBetween(50_000_000, 500_000_000),
        ];
    }
}
