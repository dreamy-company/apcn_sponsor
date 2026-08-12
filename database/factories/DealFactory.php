<?php

namespace Database\Factories;

use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Package;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deal_number' => 'J4U-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'doctor_id' => User::factory()->doctor(),
            'sponsor_id' => Sponsor::factory(),
            'package_id' => Package::factory(),
            'final_price' => fake()->numberBetween(10_000_000, 500_000_000),
            'status' => DealStatus::Draft,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DealStatus::Finalized,
        ]);
    }
}
