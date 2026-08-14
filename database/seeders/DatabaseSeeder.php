<?php

namespace Database\Seeders;

use App\Enums\DealStatus;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the admin login + non-login doctors, the sponsorship catalog, the
     * global public access code, and one finalized example deal.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $this->call(SponsorCatalogSeeder::class);

        Setting::set('public_access_code', 'APCN2027');

        $this->seedExampleDeal();
    }

    /**
     * One finalized deal with payment terms and generated material checklist.
     */
    protected function seedExampleDeal(): void
    {
        $doctor = User::doctors()->orderBy('id')->firstOrFail();
        $diamond = Package::where('name', 'Diamond')->firstOrFail();

        $sponsor = Sponsor::create([
            'company_name' => 'PT Pharma Sejahtera',
            'pic_name' => 'Rina Wijaya',
            'pic_contact' => '+62 812 9000 1000',
        ]);

        $deal = Deal::create([
            'deal_number' => 'J4U-2027-0001',
            'doctor_id' => $doctor->id,
            'sponsor_id' => $sponsor->id,
            'package_id' => $diamond->id,
            'final_price' => 550_000_000,
            'status' => DealStatus::Draft,
        ]);

        $deal->items()->attach(
            $diamond->items->mapWithKeys(fn ($item): array => [
                $item->id => ['is_addon' => false, 'custom_price' => null],
            ])->all()
        );

        $addon = Item::where('name', 'E-Poster Recognition')->firstOrFail();
        $deal->items()->attach($addon->id, ['is_addon' => true, 'custom_price' => 50_000_000]);

        $deal->paymentTerms()->createMany([
            ['description' => 'Termin 1 (DP 50%)', 'due_date' => now()->addMonths(2)->toDateString(), 'amount' => 275_000_000, 'status' => PaymentStatus::Paid],
            ['description' => 'Termin 2 (50%)', 'due_date' => now()->addMonths(8)->toDateString(), 'amount' => 275_000_000, 'status' => PaymentStatus::Pending],
        ]);

        // Fires DealFinalized -> material checklist generation (observers active in seeder).
        $deal->update(['status' => DealStatus::Finalized]);
    }
}
