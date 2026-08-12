<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\PaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTerm>
 */
class PaymentTermFactory extends Factory
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
            'description' => 'Termin '.fake()->randomDigitNotNull(),
            'due_date' => fake()->date(),
            'amount' => fake()->numberBetween(5_000_000, 200_000_000),
            'status' => PaymentStatus::Pending,
        ];
    }
}
