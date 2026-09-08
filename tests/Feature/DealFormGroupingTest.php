<?php

namespace Tests\Feature;

use App\Livewire\DealForm;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealFormGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->j4u()->create());
    }

    public function test_chosen_items_are_grouped_apart_from_the_rest(): void
    {
        $inPackage = Item::factory()->create(['name' => 'Booth']);
        $spare = Item::factory()->create(['name' => 'Banner']);

        $package = Package::factory()->create();
        $package->items()->attach($inPackage->id, ['quantity' => 2]);

        $component = Livewire::test(DealForm::class)->set('packageId', $package->id);

        $items = $component->get('items');
        $selected = $component->viewData('selectedItemKeys');
        $available = $component->viewData('availableItemKeys');

        $this->assertSame([$inPackage->id], array_map(fn ($i): int => $items[$i]['item_id'], $selected));
        $this->assertSame([$spare->id], array_map(fn ($i): int => $items[$i]['item_id'], $available));
    }

    public function test_every_item_appears_in_exactly_one_group(): void
    {
        Item::factory()->count(5)->create();

        $component = Livewire::test(DealForm::class);

        $selected = $component->viewData('selectedItemKeys');
        $available = $component->viewData('availableItemKeys');

        $this->assertSame([], array_intersect($selected, $available));
        $this->assertCount(count($component->get('items')), array_merge($selected, $available));
    }

    public function test_search_narrows_only_the_available_group(): void
    {
        $chosen = Item::factory()->create(['name' => 'Alpha Booth']);
        Item::factory()->create(['name' => 'Zebra Banner']);
        Item::factory()->create(['name' => 'Omega Stand']);

        $component = Livewire::test(DealForm::class);

        foreach ($component->get('items') as $i => $row) {
            if ($row['item_id'] === $chosen->id) {
                $component->set("items.$i.checked", true);
            }
        }

        $component->set('itemSearch', 'Zebra');

        $items = $component->get('items');

        // The chosen item stays put even though it does not match the search.
        $this->assertSame(
            [$chosen->id],
            array_map(fn ($i): int => $items[$i]['item_id'], $component->viewData('selectedItemKeys'))
        );

        $available = $component->viewData('availableItemKeys');
        $this->assertCount(1, $available);
        $this->assertSame('Zebra Banner', $items[$available[0]]['name']);
    }

    public function test_the_underlying_items_array_is_never_re_sorted(): void
    {
        // wire:model binds to array indexes, so the order must survive grouping.
        Item::factory()->create(['name' => 'Zebra']);
        Item::factory()->create(['name' => 'Alpha']);

        $component = Livewire::test(DealForm::class);
        $before = array_column($component->get('items'), 'name');

        foreach ($component->get('items') as $i => $row) {
            if ($row['name'] === 'Zebra') {
                $component->set("items.$i.checked", true);
            }
        }

        $this->assertSame($before, array_column($component->get('items'), 'name'));
    }

    public function test_money_fields_are_seeded_without_a_trailing_decimal(): void
    {
        // This is what produced the stray ",00" in the price inputs.
        $this->assertSame('452500000', Money::plain('452500000.00'));
        $this->assertSame('25000.5', Money::plain('25000.50'));
        $this->assertSame('', Money::plain(null));
        $this->assertSame('', Money::plain(''));
        $this->assertSame('1000', Money::plain(1000));
    }

    public function test_an_addon_price_reaches_the_form_without_trailing_zeros(): void
    {
        $addon = Item::factory()->create(['default_price_idr' => 90_500_000]);

        $component = Livewire::test(DealForm::class);
        $row = collect($component->get('items'))->firstWhere('item_id', $addon->id);

        $this->assertSame('90500000', $row['custom_price']);
    }
}
