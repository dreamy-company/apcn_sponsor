<?php

namespace Tests\Feature;

use App\Actions\Deal\FinalizeDealAction;
use App\Enums\DealStatus;
use App\Exceptions\QuotaExceededException;
use App\Livewire\Catalog\CatalogPackageForm;
use App\Livewire\DealForm;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemQuantityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->j4u()->create());
    }

    public function test_a_package_stores_how_many_units_of_an_item_it_bundles(): void
    {
        $booth = Item::factory()->create(['name' => 'Booth 3x3m']);
        $banner = Item::factory()->create(['name' => 'T-Banner']);

        Livewire::test(CatalogPackageForm::class)
            ->set('name', 'Diamond')
            ->set('defaultPriceIdr', '2353000000')
            ->set('selectedItems', [$booth->id, $banner->id])
            ->set("itemQuantities.{$booth->id}", 5)
            ->set("itemQuantities.{$banner->id}", 2)
            ->call('save');

        $package = Package::where('name', 'Diamond')->firstOrFail();

        $this->assertSame(5, (int) $package->items->firstWhere('id', $booth->id)->pivot->quantity);
        $this->assertSame(2, (int) $package->items->firstWhere('id', $banner->id)->pivot->quantity);
    }

    public function test_an_item_without_an_explicit_quantity_defaults_to_one(): void
    {
        $item = Item::factory()->create();

        Livewire::test(CatalogPackageForm::class)
            ->set('name', 'Supporting')
            ->set('defaultPriceIdr', '235300000')
            ->set('selectedItems', [$item->id])
            ->call('save');

        $package = Package::where('name', 'Supporting')->firstOrFail();

        $this->assertSame(1, (int) $package->items->first()->pivot->quantity);
    }

    public function test_the_wizard_inherits_bundled_quantities_from_the_chosen_tier(): void
    {
        $booth = Item::factory()->create();
        $package = Package::factory()->create();
        $package->items()->attach($booth->id, ['quantity' => 5]);

        $component = Livewire::test(DealForm::class)->set('packageId', $package->id);

        $row = collect($component->get('items'))->firstWhere('item_id', $booth->id);

        $this->assertTrue($row['checked']);
        $this->assertSame(5, $row['quantity']);
    }

    public function test_subtotal_multiplies_an_addon_by_its_quantity(): void
    {
        $addon = Item::factory()->create(['default_price_idr' => 90_500_000]);

        $component = Livewire::test(DealForm::class);

        foreach ($component->get('items') as $i => $row) {
            if ($row['item_id'] === $addon->id) {
                $component->set("items.$i.checked", true)->set("items.$i.quantity", 3);
            }
        }

        $this->assertSame(271_500_000.0, $component->instance()->subtotal());
    }

    public function test_quota_is_consumed_in_units_not_deals(): void
    {
        $booth = Item::factory()->create(['quota' => 10]);

        $deal = Deal::factory()->finalized()->create(['package_id' => null]);
        $deal->items()->attach($booth->id, ['is_addon' => false, 'quantity' => 4]);

        // One deal, but it holds four of the ten units.
        $this->assertSame(4, app(QuotaService::class)->itemTakenCount($booth->id));
    }

    public function test_finalizing_is_blocked_when_the_units_requested_exceed_what_remains(): void
    {
        $booth = Item::factory()->create(['quota' => 5]);

        $taken = Deal::factory()->finalized()->create(['package_id' => null]);
        $taken->items()->attach($booth->id, ['is_addon' => true, 'quantity' => 3]);

        // 3 taken + 3 requested > 5.
        $draft = Deal::factory()->create(['package_id' => null, 'final_price' => 1_000]);
        $draft->items()->attach($booth->id, ['is_addon' => true, 'quantity' => 3]);
        PaymentTerm::factory()->create(['deal_id' => $draft->id, 'amount' => 1_000]);

        $this->expectException(QuotaExceededException::class);

        app(FinalizeDealAction::class)->execute($draft->fresh());
    }

    public function test_finalizing_succeeds_when_the_units_still_fit(): void
    {
        $booth = Item::factory()->create(['quota' => 5]);

        $taken = Deal::factory()->finalized()->create(['package_id' => null]);
        $taken->items()->attach($booth->id, ['is_addon' => true, 'quantity' => 3]);

        $draft = Deal::factory()->create(['package_id' => null, 'final_price' => 1_000]);
        $draft->items()->attach($booth->id, ['is_addon' => true, 'quantity' => 2]);
        PaymentTerm::factory()->create(['deal_id' => $draft->id, 'amount' => 1_000]);

        app(FinalizeDealAction::class)->execute($draft->fresh());

        $this->assertSame(DealStatus::Finalized, $draft->fresh()->status);
    }

    public function test_quantities_are_persisted_when_a_deal_is_saved(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Unit')
            ->set('brandName', 'Unit Brand')
            ->set('picName', 'Rudi')
            ->set('picContact', '0812')
            ->set('packageId', null)
            ->set('finalPrice', '1000')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'quantity' => 7, 'catalog_inclusion' => '', 'is_addon' => true, 'checked' => true, 'custom_price' => '100'],
            ])
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'Lunas', 'due_date' => '2027-01-15', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $deal = Deal::whereHas('sponsor', fn ($q) => $q->where('company_name', 'PT Unit'))->firstOrFail();

        $this->assertSame(7, (int) $deal->items()->first()->pivot->quantity);
    }
}
