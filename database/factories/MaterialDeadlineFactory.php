<?php

namespace Database\Factories;

use App\Enums\MaterialStatus;
use App\Models\Deal;
use App\Models\Item;
use App\Models\MaterialDeadline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialDeadline>
 */
class MaterialDeadlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
            'item_id' => Item::factory(),
            'material_name' => fake()->words(3, true),
            'due_date' => fake()->date(),
            'status' => MaterialStatus::Pending,
            'received_at' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MaterialStatus::Received,
            'received_at' => now(),
        ]);
    }
}
