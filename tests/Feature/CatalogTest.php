<?php

namespace Tests\Feature;

use App\Livewire\Catalog\CatalogItemForm;
use App\Livewire\Catalog\CatalogItemIndex;
use App\Livewire\Catalog\CatalogPackageForm;
use App\Livewire\Catalog\CatalogPackageIndex;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_cannot_access_catalog_routes(): void
    {
        $doctor = User::factory()->doctor()->create();
        $this->actingAs($doctor);

        $item = Item::factory()->create();
        $package = Package::factory()->create();

        $this->get(route('catalog.items.index'))->assertForbidden();
        $this->get(route('catalog.items.create'))->assertForbidden();
        $this->get(route('catalog.items.edit', $item))->assertForbidden();
        $this->get(route('catalog.packages.index'))->assertForbidden();
        $this->get(route('catalog.packages.create'))->assertForbidden();
        $this->get(route('catalog.packages.edit', $package))->assertForbidden();
    }

    public function test_j4u_can_access_catalog_routes(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        $item = Item::factory()->create();
        $package = Package::factory()->create();

        $this->get(route('catalog.items.index'))->assertOk();
        $this->get(route('catalog.items.create'))->assertOk();
        $this->get(route('catalog.items.edit', $item))->assertOk();
        $this->get(route('catalog.packages.index'))->assertOk();
        $this->get(route('catalog.packages.create'))->assertOk();
        $this->get(route('catalog.packages.edit', $package))->assertOk();
    }

    public function test_j4u_can_create_an_item(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(CatalogItemForm::class)
            ->set('name', 'Booth 3x3m')
            ->set('type', 'booth')
            ->set('requiresMaterial', true)
            ->call('save');

        $this->assertDatabaseHas('items', [
            'name' => 'Booth 3x3m',
            'type' => 'booth',
            'requires_material' => 1,
        ]);
    }

    public function test_j4u_can_edit_an_item(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $item = Item::factory()->withoutMaterial()->create();

        Livewire::test(CatalogItemForm::class, ['item' => $item])
            ->set('name', 'Booth 6x6m')
            ->set('type', 'booth')
            ->set('requiresMaterial', true)
            ->call('save');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Booth 6x6m',
            'requires_material' => 1,
        ]);
    }

    public function test_j4u_can_create_a_package_with_items(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $itemA = Item::factory()->create();
        $itemB = Item::factory()->create();

        Livewire::test(CatalogPackageForm::class)
            ->set('name', 'Diamond')
            ->set('defaultPrice', '500000000')
            ->set('selectedItems', [$itemA->id, $itemB->id])
            ->call('save');

        $package = Package::where('name', 'Diamond')->first();

        $this->assertNotNull($package);
        $this->assertSame('500000000.00', $package->default_price);
        $this->assertSame(2, $package->items()->count());
    }

    public function test_j4u_can_edit_a_package_and_sync_items(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $package = Package::factory()->create();
        $itemA = Item::factory()->create();
        $itemB = Item::factory()->create();
        $package->items()->attach([$itemA->id, $itemB->id]);

        Livewire::test(CatalogPackageForm::class, ['package' => $package])
            ->set('name', 'Diamond Plus')
            ->set('defaultPrice', '550000000')
            ->set('selectedItems', [$itemB->id])
            ->call('save');

        $this->assertSame('Diamond Plus', $package->fresh()->name);
        $this->assertSame(1, $package->fresh()->items()->count());
        $this->assertTrue($package->fresh()->items()->where('items.id', $itemB->id)->exists());
    }

    public function test_item_used_in_a_deal_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $deal = Deal::factory()->create();
        $item = Item::factory()->create();
        $deal->items()->attach($item->id);

        Livewire::test(CatalogItemIndex::class)->call('delete', $item->id);

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_unused_item_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $item = Item::factory()->create();

        Livewire::test(CatalogItemIndex::class)->call('delete', $item->id);

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_package_used_in_a_deal_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $package = Package::factory()->create();
        Deal::factory()->create(['package_id' => $package->id]);

        Livewire::test(CatalogPackageIndex::class)->call('delete', $package->id);

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_unused_package_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $package = Package::factory()->create();

        Livewire::test(CatalogPackageIndex::class)->call('delete', $package->id);

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }
}
