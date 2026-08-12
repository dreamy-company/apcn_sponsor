<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Package;
use Illuminate\Database\Seeder;

class SponsorCatalogSeeder extends Seeder
{
    /**
     * Seed the sponsorship catalog (items + package tiers) per the PRD prospectus examples.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Booth 3x3m', 'type' => 'booth', 'requires_material' => true],
            ['name' => 'Booth 6x6m', 'type' => 'booth', 'requires_material' => true],
            ['name' => 'Industry Symposium', 'type' => 'symposium', 'requires_material' => true],
            ['name' => 'Satellite Symposium', 'type' => 'symposium', 'requires_material' => true],
            ['name' => 'Welcome Reception Naming Rights', 'type' => 'naming', 'requires_material' => true],
            ['name' => 'Lanyard Branding', 'type' => 'advertising', 'requires_material' => true],
            ['name' => 'Flyer / Bag Insert', 'type' => 'advertising', 'requires_material' => true],
            ['name' => 'E-Poster Recognition', 'type' => 'digital', 'requires_material' => false],
            ['name' => 'Virtual Booth', 'type' => 'digital', 'requires_material' => false],
        ];

        $catalog = collect($items)
            ->map(fn (array $item): Item => Item::create($item))
            ->keyBy('name');

        $packages = [
            'Diamond' => ['price' => 500_000_000, 'items' => ['Booth 6x6m', 'Industry Symposium', 'Welcome Reception Naming Rights', 'Lanyard Branding']],
            'Platinum' => ['price' => 300_000_000, 'items' => ['Booth 3x3m', 'Satellite Symposium', 'Lanyard Branding']],
            'White Gold' => ['price' => 150_000_000, 'items' => ['Booth 3x3m', 'E-Poster Recognition']],
            'Gold' => ['price' => 75_000_000, 'items' => ['Booth 3x3m']],
            'Silver' => ['price' => 40_000_000, 'items' => ['Virtual Booth', 'Flyer / Bag Insert']],
        ];

        foreach ($packages as $name => $data) {
            $package = Package::create(['name' => $name, 'default_price' => $data['price']]);
            $package->items()->attach(collect($data['items'])->map(fn (string $itemName): int => $catalog[$itemName]->id)->all());
        }
    }
}
