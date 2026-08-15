<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\DealAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealAsset>
 */
class DealAssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(2).'.pdf';

        return [
            'deal_id' => Deal::factory(),
            'disk' => 'public',
            'path' => 'deal-assets/'.fake()->uuid().'/'.$name,
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1_000_000, 40_000_000),
            'uploaded_by_id' => null,
        ];
    }
}
