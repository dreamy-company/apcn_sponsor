<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Seeds the sponsorship catalog from the APCN & AM InaSN 2027 Sponsorship
 * Prospectus (Draft Prospectus Catalogue, light edition).
 *
 * Prices are the IDR figures printed in the prospectus (USD x 18,100).
 * Item `default_price` is the add-on / rate-card price of a single unit;
 * `quota` is the number of units or sponsor slots available.
 */
class SponsorCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = collect($this->items())
            ->map(fn (array $item): Item => Item::create($item))
            ->keyBy('name');

        foreach ($this->packages() as $name => $data) {
            $package = Package::create([
                'name' => $name,
                'default_price' => $data['price'],
                'quota' => $data['quota'],
            ]);

            $package->items()->attach(
                collect($data['items'])->map(fn (string $itemName): int => $catalog[$itemName]->id)->all()
            );
        }
    }

    /**
     * Catalog items — the "Tier Inclusion & Value Matrix" plus the
     * "Add-On Opportunities" tables of the prospectus.
     *
     * @return list<array{name: string, type: string, quota: int|null, default_price: int, requires_material: bool}>
     */
    protected function items(): array
    {
        return [
            // --- Exhibition & registration -----------------------------------
            ['name' => 'Booth 3x3m', 'type' => 'booth', 'quota' => 122, 'default_price' => 108_600_000, 'requires_material' => true],
            ['name' => 'Complimentary Registration (per pax)', 'type' => 'registration', 'quota' => null, 'default_price' => 7_240_000, 'requires_material' => false],

            // --- Scientific program ------------------------------------------
            ['name' => 'Industry Symposium — Mangupura Hall (1,500 pax)', 'type' => 'symposium', 'quota' => 6, 'default_price' => 995_500_000, 'requires_material' => true],
            ['name' => 'Industry Symposium — Nusantara 1&2 / Jakarta AB (500 pax)', 'type' => 'symposium', 'quota' => 6, 'default_price' => 895_950_000, 'requires_material' => true],
            ['name' => 'Industry Symposium — Auditorium Hall 4 (500 pax)', 'type' => 'symposium', 'quota' => 3, 'default_price' => 895_950_000, 'requires_material' => true],
            ['name' => 'Satellite Symposium (20–30 min)', 'type' => 'symposium', 'quota' => 12, 'default_price' => 543_000_000, 'requires_material' => true],
            ['name' => 'Spotlight Session — Exhibition Gallery (20–30 min)', 'type' => 'symposium', 'quota' => null, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Interventional Workshop (Full Day, per room)', 'type' => 'symposium', 'quota' => 3, 'default_price' => 1_086_000_000, 'requires_material' => true],
            ['name' => 'Pre-Congress Sponsorship (per full day / room)', 'type' => 'symposium', 'quota' => 4, 'default_price' => 905_000_000, 'requires_material' => true],

            // --- Naming rights & hospitality ---------------------------------
            ['name' => 'Gala Dinner Naming Rights', 'type' => 'naming', 'quota' => 3, 'default_price' => 1_176_500_000, 'requires_material' => true],
            ['name' => 'Faculty Dinner Naming Rights (By Invitation)', 'type' => 'naming', 'quota' => 1, 'default_price' => 362_000_000, 'requires_material' => true],
            ['name' => 'Welcome Reception Sponsor', 'type' => 'naming', 'quota' => 3, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Coffee Break Sponsorship (per session)', 'type' => 'naming', 'quota' => 6, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Travel Grant Naming Sponsorship (per 3 pax)', 'type' => 'naming', 'quota' => null, 'default_price' => 72_400_000, 'requires_material' => false],
            ['name' => 'Meeting Room — Private Networking Room (per room)', 'type' => 'facility', 'quota' => 4, 'default_price' => 271_500_000, 'requires_material' => false],
            ['name' => 'Faculty Lounge / VIP Room Branding', 'type' => 'branding', 'quota' => 4, 'default_price' => 497_750_000, 'requires_material' => true],
            ['name' => 'Doctor Lounge Branding', 'type' => 'branding', 'quota' => 3, 'default_price' => 271_500_000, 'requires_material' => true],

            // --- Physical branding -------------------------------------------
            ['name' => 'Delegate Bag Sponsorship', 'type' => 'branding', 'quota' => 3, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Event Mascot Branding', 'type' => 'branding', 'quota' => 3, 'default_price' => 362_000_000, 'requires_material' => true],
            ['name' => 'Photobooth Branding', 'type' => 'branding', 'quota' => null, 'default_price' => 90_500_000, 'requires_material' => true],
            ['name' => 'Logo on Welcome Gate', 'type' => 'branding', 'quota' => null, 'default_price' => 90_500_000, 'requires_material' => true],
            ['name' => 'Branding Shuttle Bus (per unit)', 'type' => 'branding', 'quota' => 12, 'default_price' => 90_500_000, 'requires_material' => true],
            ['name' => 'Charging Station Branding (per unit / 3 days)', 'type' => 'branding', 'quota' => 7, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Floor Stickers (per point)', 'type' => 'branding', 'quota' => 15, 'default_price' => 90_500_000, 'requires_material' => true],
            ['name' => 'T-Banner (per point)', 'type' => 'branding', 'quota' => 40, 'default_price' => 108_600_000, 'requires_material' => true],
            ['name' => 'Hanging Banner (per flag, 2-sided)', 'type' => 'branding', 'quota' => null, 'default_price' => 90_500_000, 'requires_material' => true],
            ['name' => 'LED Screen Lower Branding (2m × 9m)', 'type' => 'branding', 'quota' => 9, 'default_price' => 181_000_000, 'requires_material' => true],
            ['name' => 'LED Screen Pillar (shared / rotating)', 'type' => 'branding', 'quota' => null, 'default_price' => 181_000_000, 'requires_material' => true],
            ['name' => 'Opening & Closing Slide Logo', 'type' => 'branding', 'quota' => null, 'default_price' => 90_500_000, 'requires_material' => true],
            ['name' => 'Logo on E-Poster Touch Screen (per TV unit)', 'type' => 'branding', 'quota' => 10, 'default_price' => 325_800_000, 'requires_material' => true],
            ['name' => 'E-Pocket Program', 'type' => 'branding', 'quota' => null, 'default_price' => 271_500_000, 'requires_material' => true],

            // --- Digital -------------------------------------------------------
            ['name' => 'Homepage Banner (Flagship)', 'type' => 'digital', 'quota' => 3, 'default_price' => 452_500_000, 'requires_material' => true],
            ['name' => 'Registration Page + QR Self-Registration System', 'type' => 'digital', 'quota' => 3, 'default_price' => 362_000_000, 'requires_material' => true],
            ['name' => 'Scientific Landing Page Website', 'type' => 'digital', 'quota' => null, 'default_price' => 181_000_000, 'requires_material' => true],
            ['name' => 'WiFi Special Display', 'type' => 'digital', 'quota' => 3, 'default_price' => 325_800_000, 'requires_material' => true],
            ['name' => 'Mobile App Banner (Full)', 'type' => 'digital', 'quota' => 4, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Mobile App Sponsorship (Logo)', 'type' => 'digital', 'quota' => 7, 'default_price' => 271_500_000, 'requires_material' => true],
            ['name' => 'Push Notification (App, per notification)', 'type' => 'digital', 'quota' => null, 'default_price' => 135_750_000, 'requires_material' => true],
            ['name' => 'Email Banner (per blast)', 'type' => 'digital', 'quota' => null, 'default_price' => 72_400_000, 'requires_material' => true],
            ['name' => 'Email Banner — Whole Event', 'type' => 'digital', 'quota' => 4, 'default_price' => 362_000_000, 'requires_material' => true],
            ['name' => 'Social Media Sponsor Package', 'type' => 'digital', 'quota' => null, 'default_price' => 325_800_000, 'requires_material' => true],
        ];
    }

    /**
     * The seven tiers, priced per the "Seven Tiers of Partnership" table.
     * `quota` is the Max Sponsors count; items follow the Tier Inclusion Matrix.
     *
     * @return array<string, array{price: int, quota: int, items: list<string>}>
     */
    protected function packages(): array
    {
        $core = 'Opening & Closing Slide Logo';
        $booth = 'Booth 3x3m';
        $reg = 'Complimentary Registration (per pax)';
        $tbanner = 'T-Banner (per point)';

        return [
            'Diamond' => ['price' => 2_353_000_000, 'quota' => 3, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Industry Symposium — Mangupura Hall (1,500 pax)',
                'Satellite Symposium (20–30 min)',
                'Coffee Break Sponsorship (per session)',
                'Delegate Bag Sponsorship',
                'WiFi Special Display',
                'Homepage Banner (Flagship)',
                'Registration Page + QR Self-Registration System',
                'Photobooth Branding',
                'Logo on Welcome Gate',
            ]],
            'Platinum' => ['price' => 1_810_000_000, 'quota' => 4, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Industry Symposium — Nusantara 1&2 / Jakarta AB (500 pax)',
                'Welcome Reception Sponsor',
                'Faculty Lounge / VIP Room Branding',
                'Mobile App Banner (Full)',
                'Meeting Room — Private Networking Room (per room)',
            ]],
            'White Gold' => ['price' => 1_448_000_000, 'quota' => 3, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Industry Symposium — Auditorium Hall 4 (500 pax)',
                'Doctor Lounge Branding',
                'Event Mascot Branding',
                'Scientific Landing Page Website',
                'Push Notification (App, per notification)',
                'Logo on Welcome Gate',
            ]],
            'Gold' => ['price' => 1_086_000_000, 'quota' => 9, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Satellite Symposium (20–30 min)',
                'Email Banner (per blast)',
                'Spotlight Session — Exhibition Gallery (20–30 min)',
                'LED Screen Lower Branding (2m × 9m)',
            ]],
            'Silver' => ['price' => 724_000_000, 'quota' => 7, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Satellite Symposium (20–30 min)',
                'Charging Station Branding (per unit / 3 days)',
                'Mobile App Sponsorship (Logo)',
            ]],
            'Bronze' => ['price' => 452_500_000, 'quota' => 7, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Branding Shuttle Bus (per unit)',
                'Hanging Banner (per flag, 2-sided)',
            ]],
            'Supporting' => ['price' => 235_300_000, 'quota' => 20, 'items' => [
                $booth, $reg, $core, $tbanner,
                'Hanging Banner (per flag, 2-sided)',
                'Photobooth Branding',
            ]],
        ];
    }
}
