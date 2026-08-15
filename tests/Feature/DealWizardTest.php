<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Livewire\DealForm;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_is_blocked_until_the_step_validates(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(DealForm::class)
            ->call('nextStep')
            ->assertHasErrors(['doctorId', 'companyName', 'picName', 'picContact'])
            ->assertSet('currentStep', 1);
    }

    public function test_step_two_requires_at_least_one_item(): void
    {
        $this->actingAs(User::factory()->j4u()->create());
        $doctor = User::factory()->doctor()->create();
        Item::factory()->create(); // exists but not part of any package → add-on, unchecked

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Contoh')
            ->set('picName', 'Budi')
            ->set('picContact', '0812')
            ->call('nextStep')          // → step 2
            ->set('finalPrice', '1000')
            ->call('nextStep')          // no items checked
            ->assertHasErrors('items')
            ->assertSet('currentStep', 2);
    }

    public function test_cannot_jump_to_an_unvisited_step(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(DealForm::class)
            ->call('goToStep', 4)
            ->assertSet('currentStep', 1);
    }

    public function test_full_wizard_creates_a_deal(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();
        $package = Package::factory()->create(['default_price' => 250_000_000]);
        $package->items()->attach($item->id);

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Contoh Sejahtera')
            ->set('picName', 'Budi Santoso')
            ->set('picContact', '+62 812 0000 0000')
            ->call('nextStep')                       // → step 2
            ->assertSet('currentStep', 2)
            ->set('packageId', $package->id)         // base item auto-checked, price prefilled
            ->call('nextStep')                       // → step 3
            ->assertSet('currentStep', 3)
            ->set('paymentTerms', [
                ['description' => 'Termin 1', 'due_date' => '2027-01-15', 'amount' => '250000000'],
            ])
            ->call('nextStep')                       // → step 4 (Summary)
            ->assertSet('currentStep', 4)
            ->call('save');

        $this->assertDatabaseHas('deals', [
            'doctor_id' => $doctor->id,
            'package_id' => $package->id,
            'status' => DealStatus::Draft->value,
        ]);
    }
}
