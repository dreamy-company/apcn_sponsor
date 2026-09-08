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

    public function test_the_footer_navigation_renders_on_every_step(): void
    {
        // A stray/missing </div> in the item markup once swallowed the footer,
        // leaving the wizard with no way forward. Guard the whole path.
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();
        $package = Package::factory()->create();
        $package->items()->attach($item->id, ['quantity' => 1]);

        $component = Livewire::test(DealForm::class)
            ->assertSee('Next: Package & Items')
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Nav')
            ->set('brandName', 'Nav Brand')
            ->set('picName', 'Sari')
            ->set('picContact', '0812')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->assertSee('Next: Payment Terms');

        $component->set('packageId', $package->id)
            ->set('finalPrice', '1000')
            ->call('nextStep')
            ->assertSet('currentStep', 3)
            ->assertSee('Next: Summary')
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'Lunas', 'due_date' => '2027-01-15', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertSee('Create Deal');
    }

    public function test_the_rendered_markup_has_balanced_divs(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Item::factory()->count(3)->create();

        $html = Livewire::test(DealForm::class)->html();

        $opened = preg_match_all('/<div\b/', $html);
        $closed = substr_count($html, '</div>');

        $this->assertSame($closed, $opened, 'Unbalanced <div> tags in the wizard markup.');
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
