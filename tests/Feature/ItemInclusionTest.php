<?php

namespace Tests\Feature;

use App\Livewire\Catalog\CatalogItemForm;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_a_deal_falls_back_to_the_catalog_inclusion(): void
    {
        $item = Item::factory()->create(['inclusion' => 'Termasuk 2 kursi.']);

        $deal = Deal::factory()->create(['package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true, 'quantity' => 1]);

        $pivot = $deal->fresh()->items->first()->pivot;

        $this->assertNull($pivot->inclusion);
        $this->assertSame('Termasuk 2 kursi.', $pivot->effectiveInclusion());
    }

    public function test_a_per_deal_override_wins_over_the_catalog(): void
    {
        $item = Item::factory()->create(['inclusion' => 'Termasuk 2 kursi.']);

        $deal = Deal::factory()->create(['package_id' => null]);
        $deal->items()->attach($item->id, [
            'is_addon' => true,
            'quantity' => 1,
            'inclusion' => 'Khusus deal ini: 4 kursi.',
        ]);

        $this->assertSame(
            'Khusus deal ini: 4 kursi.',
            $deal->fresh()->items->first()->pivot->effectiveInclusion()
        );
    }

    public function test_a_blank_override_falls_back_rather_than_showing_nothing(): void
    {
        $item = Item::factory()->create(['inclusion' => 'Termasuk 2 kursi.']);

        $deal = Deal::factory()->create(['package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true, 'quantity' => 1, 'inclusion' => '   ']);

        $this->assertSame('Termasuk 2 kursi.', $deal->fresh()->items->first()->pivot->effectiveInclusion());
    }

    public function test_the_wizard_saves_a_per_deal_override(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create(['inclusion' => 'Default katalog.']);

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Inclusion')
            ->set('brandName', 'Inclusion Brand')
            ->set('picName', 'Sari')
            ->set('picContact', '0812')
            ->set('packageId', null)
            ->set('finalPrice', '1000')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'quantity' => 1,
                    'inclusion' => 'Versi deal ini.', 'catalog_inclusion' => 'Default katalog.',
                    'is_addon' => true, 'checked' => true, 'custom_price' => '1000'],
            ])
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'Lunas', 'due_date' => '2027-01-15', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $deal = Deal::whereHas('sponsor', fn ($q) => $q->where('company_name', 'PT Inclusion'))->firstOrFail();

        $this->assertSame('Versi deal ini.', $deal->items->first()->pivot->inclusion);
    }

    public function test_the_deal_page_shows_the_effective_inclusion(): void
    {
        $item = Item::factory()->create(['inclusion' => 'Termasuk dua kursi.']);

        $deal = Deal::factory()->create(['package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true, 'quantity' => 1]);

        Livewire::test(DealShow::class, ['deal' => $deal->fresh()])
            ->assertOk()
            ->assertSee('Termasuk dua kursi.');
    }
}
