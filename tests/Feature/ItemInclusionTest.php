<?php

namespace Tests\Feature;

use App\Livewire\Catalog\CatalogItemForm;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ItemInclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->j4u()->create());
    }

    public function test_an_item_stores_its_catalog_inclusion(): void
    {
        Livewire::test(CatalogItemForm::class)
            ->set('name', 'Booth 3x3m')
            ->set('inclusion', "1 unit booth 3x3m\n2 kursi, 1 meja")
            ->call('save');

        $item = Item::where('name', 'Booth 3x3m')->firstOrFail();

        $this->assertSame("1 unit booth 3x3m\n2 kursi, 1 meja", $item->inclusion);
    }

    public function test_inclusion_is_written_once_per_deal_not_per_item(): void
    {
        // The per-item override was removed: one field covers the whole deal.
        $this->assertTrue(Schema::hasColumn('deals', 'inclusion'));
        $this->assertFalse(Schema::hasColumn('deal_items', 'inclusion'));
    }

    public function test_the_wizard_saves_the_deal_inclusion(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Inclusion')
            ->set('brandName', 'Inclusion Brand')
            ->set('picName', 'Sari')
            ->set('picContact', '0812')
            ->set('packageId', null)
            ->set('finalPrice', '1000')
            ->set('inclusion', "Booth 3x3m di lokasi utama\n15 registrasi gratis")
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'quantity' => 1,
                    'catalog_inclusion' => '', 'is_addon' => true, 'checked' => true, 'custom_price' => '1000'],
            ])
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'Lunas', 'due_date' => '2027-01-15', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $deal = Deal::whereHas('sponsor', fn ($q) => $q->where('company_name', 'PT Inclusion'))->firstOrFail();

        $this->assertSame("Booth 3x3m di lokasi utama\n15 registrasi gratis", $deal->inclusion);
    }

    public function test_a_blank_inclusion_is_stored_as_null(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Kosong')
            ->set('brandName', 'Kosong Brand')
            ->set('picName', 'Sari')
            ->set('picContact', '0812')
            ->set('packageId', null)
            ->set('finalPrice', '1000')
            ->set('inclusion', '   ')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'quantity' => 1,
                    'catalog_inclusion' => '', 'is_addon' => true, 'checked' => true, 'custom_price' => '1000'],
            ])
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'Lunas', 'due_date' => '2027-01-15', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('save');

        $deal = Deal::whereHas('sponsor', fn ($q) => $q->where('company_name', 'PT Kosong'))->firstOrFail();

        $this->assertNull($deal->inclusion);
    }

    public function test_editing_a_deal_keeps_its_inclusion(): void
    {
        $item = Item::factory()->create();
        $deal = Deal::factory()->create(['package_id' => null, 'inclusion' => 'Teks awal.']);
        $deal->items()->attach($item->id, ['is_addon' => true, 'quantity' => 1]);

        Livewire::test(DealForm::class, ['deal' => $deal])
            ->assertSet('inclusion', 'Teks awal.')
            ->set('inclusion', 'Teks revisi.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Teks revisi.', $deal->fresh()->inclusion);
    }

    public function test_the_deal_page_shows_the_deal_inclusion_and_the_catalog_blurb(): void
    {
        $item = Item::factory()->create(['inclusion' => 'Blurb katalog item.']);

        $deal = Deal::factory()->create([
            'package_id' => null,
            'inclusion' => 'Yang didapat sponsor pada deal ini.',
        ]);
        $deal->items()->attach($item->id, ['is_addon' => true, 'quantity' => 1]);

        Livewire::test(DealShow::class, ['deal' => $deal->fresh()])
            ->assertOk()
            ->assertSee('Yang didapat sponsor pada deal ini.')
            ->assertSee('Blurb katalog item.');
    }
}
