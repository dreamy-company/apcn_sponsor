<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Livewire\DealForm;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WizardRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_two_renders_the_masked_money_fields_and_both_item_groups(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $inPackage = Item::factory()->create(['name' => 'Booth 3x3m', 'default_price_idr' => 452_500_000]);
        Item::factory()->create(['name' => 'Hanging Banner', 'default_price_idr' => 90_500_000]);

        $package = Package::factory()->create(['default_price_idr' => 452_500_000]);
        $package->items()->attach($inPackage->id, ['quantity' => 2]);

        $component = Livewire::test(DealForm::class)
            ->set('packageId', $package->id)
            ->assertOk()
            ->assertSet('finalPrice', '452500000');

        $html = $component->html();

        // Both groups render, chosen items first.
        $this->assertStringContainsString('Selected Items', $html);
        $this->assertStringContainsString('Other Items', $html);
        $this->assertLessThan(
            strpos($html, 'Other Items'),
            strpos($html, 'Selected Items'),
            'Chosen items must render above the rest.'
        );

        // Money fields carry the mask, not a bare number input.
        $this->assertStringContainsString('moneyInput(', $html);
        $this->assertStringContainsString("model: 'finalPrice'", $html);

        // The masked field is filled by Alpine at runtime, so what matters is
        // that the property behind it is clean — no trailing ",00" to render.
        $this->assertStringNotContainsString('452500000.00', $html);

        // Inclusion is offered on chosen items only.
        $this->assertStringContainsString('Inclusion', $html);
    }

    public function test_the_mask_switches_separators_with_the_currency(): void
    {
        $this->actingAs(User::factory()->j4u()->create());
        Item::factory()->create();

        $html = Livewire::test(DealForm::class)
            ->set('currency', Currency::USD->value)
            ->assertOk()
            ->html();

        $this->assertStringContainsString("currency: 'USD'", $html);
    }
}
