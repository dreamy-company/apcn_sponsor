<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealCurrencyAndSubtotalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->j4u()->create());
    }

    public function test_the_currency_enum_formats_each_currency_conventionally(): void
    {
        $this->assertSame('Rp 1.000.000', Currency::IDR->format(1_000_000));
        $this->assertSame('$1,000.00', Currency::USD->format(1000));
        $this->assertSame('Rp 0', Currency::IDR->format(null));
    }

    public function test_subtotal_accumulates_the_package_plus_selected_addons(): void
    {
        $baseItem = Item::factory()->create();
        $addon = Item::factory()->create(['default_price_idr' => 50_000_000]);
        $package = Package::factory()->create(['default_price_idr' => 250_000_000]);
        $package->items()->attach($baseItem->id);

        $component = Livewire::test(DealForm::class)->set('packageId', $package->id);

        // Package alone: base items are inside the tier price and add nothing.
        $this->assertSame(250_000_000.0, $component->instance()->subtotal());

        // Check the add-on (prefilled at its catalog price) and it accumulates.
        $items = $component->get('items');
        foreach ($items as $i => $row) {
            if ($row['item_id'] === $addon->id) {
                $component->set("items.$i.checked", true);
            }
        }

        $this->assertSame(300_000_000.0, $component->instance()->subtotal());
    }

    public function test_switching_to_usd_reprices_addons_from_the_usd_column(): void
    {
        $addon = Item::factory()->create([
            'default_price_idr' => 181_000_000,
            'default_price_usd' => 10_000,
        ]);
        $package = Package::factory()->create([
            'default_price_idr' => 452_500_000,
            'default_price_usd' => 25_000,
        ]);

        $component = Livewire::test(DealForm::class)->set('packageId', $package->id);

        foreach ($component->get('items') as $i => $row) {
            if ($row['item_id'] === $addon->id) {
                $component->set("items.$i.checked", true);
            }
        }

        $this->assertSame(633_500_000.0, $component->instance()->subtotal());

        $component->set('currency', Currency::USD->value);

        $this->assertSame(35_000.0, $component->instance()->subtotal());
    }

    public function test_item_search_filters_the_grid_without_losing_selections(): void
    {
        $wanted = Item::factory()->create(['name' => 'Zebra Banner']);
        $other = Item::factory()->create(['name' => 'Alpha Booth']);

        $component = Livewire::test(DealForm::class);

        foreach ($component->get('items') as $i => $row) {
            if ($row['item_id'] === $other->id) {
                $component->set("items.$i.checked", true);
            }
        }

        $component->set('itemSearch', 'Zebra');

        // Search narrows the "other items" list to the match...
        $available = $component->viewData('availableItemKeys');
        $this->assertCount(1, $available);
        $this->assertSame($wanted->id, $component->get('items')[$available[0]]['item_id']);

        // ...while the chosen item stays visible in its own group,
        // and keeps its checked state.
        $selected = $component->viewData('selectedItemKeys');
        $this->assertCount(1, $selected);
        $this->assertSame($other->id, $component->get('items')[$selected[0]]['item_id']);

        $checked = collect($component->get('items'))->firstWhere('item_id', $other->id);
        $this->assertTrue($checked['checked']);
    }

    public function test_a_usd_deal_renders_dollar_amounts(): void
    {
        $deal = Deal::factory()->create([
            'currency' => Currency::USD,
            'final_price' => 25_000,
        ]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->assertOk()
            ->assertSee('$25,000.00')
            ->assertDontSee('Rp 25.000');
    }

    public function test_an_admin_can_create_a_doctor_inline_and_contact_is_required(): void
    {
        Livewire::test(DealForm::class)
            ->set('newDoctorName', 'Dr. Baru')
            ->call('createDoctor')
            ->assertHasErrors('newDoctorPhone');

        $this->assertDatabaseMissing('users', ['name' => 'Dr. Baru']);

        $component = Livewire::test(DealForm::class)
            ->set('newDoctorName', 'Dr. Baru')
            ->set('newDoctorPhone', '+62 812 3456 7890')
            ->call('createDoctor')
            ->assertHasNoErrors();

        $doctor = User::doctors()->where('name', 'Dr. Baru')->first();
        $this->assertNotNull($doctor);
        $this->assertNotEmpty($doctor->public_token);
        $this->assertSame($doctor->id, $component->get('doctorId'));
    }

    public function test_an_admin_can_add_a_catalog_item_inline_and_it_is_selected(): void
    {
        $component = Livewire::test(DealForm::class)
            ->set('newItemName', 'Inline Banner')
            ->set('newItemPriceIdr', '90500000')
            ->set('newItemPriceUsd', '5000')
            ->call('createItem')
            ->assertHasNoErrors();

        $item = Item::where('name', 'Inline Banner')->first();
        $this->assertNotNull($item);
        $this->assertSame('90500000.00', $item->default_price_idr);
        $this->assertSame('5000.00', $item->default_price_usd);

        $row = collect($component->get('items'))->firstWhere('item_id', $item->id);
        $this->assertTrue($row['checked']);
    }
}
