<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'type' => fake()->randomElement(['booth', 'symposium', 'naming', 'advertising']),
            'default_price' => fake()->numberBetween(10, 500) * 1_000_000,
            'requires_material' => fake()->boolean(),
        ];
    }

    public function withMaterial(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_material' => true,
        ]);
    }

    public function withoutMaterial(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_material' => false,
        ]);
    }
}
