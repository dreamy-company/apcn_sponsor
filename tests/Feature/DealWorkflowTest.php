<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\Item;
use App\Models\MaterialDeadline;
use App\Models\Package;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_j4u_can_create_a_deal_with_items_and_payment_terms(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $doctor = User::factory()->doctor()->create();
        $package = Package::factory()->create();
        $baseItem = Item::factory()->withMaterial()->create();
        $addonItem = Item::factory()->withMaterial()->create();
        $package->items()->attach($baseItem->id);

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Contoh Sejahtera')
            ->set('picName', 'Budi Santoso')
            ->set('picContact', '+62 812 0000 0000')
            ->set('packageId', $package->id)
            ->set('finalPrice', '250000000')
            ->set('items', [
                ['item_id' => $baseItem->id, 'name' => $baseItem->name, 'type' => null, 'is_addon' => false, 'checked' => true, 'custom_price' => ''],
                ['item_id' => $addonItem->id, 'name' => $addonItem->name, 'type' => null, 'is_addon' => true, 'checked' => true, 'custom_price' => '50000000'],
            ])
            ->set('paymentTerms', [
                ['description' => 'Termin 1 (DP 50%)', 'due_date' => '2027-01-15', 'amount' => '125000000'],
                ['description' => 'Termin 2', 'due_date' => '2027-06-15', 'amount' => '125000000'],
            ])
            ->call('save');

        $deal = Deal::where('doctor_id', $doctor->id)->first();

        $this->assertNotNull($deal);
        $this->assertSame(DealStatus::Draft, $deal->status);
        $this->assertSame('250000000.00', $deal->final_price);
        $this->assertSame(2, $deal->items()->count());

        $addon = $deal->items()->find($addonItem->id);
        $this->assertTrue((bool) $addon->pivot->is_addon);
        $this->assertSame('50000000.00', $addon->pivot->custom_price);

        $this->assertSame(2, $deal->paymentTerms()->count());

        // Draft deals must not generate materials yet.
        $this->assertSame(0, $deal->materialDeadlines()->count());

        // Audit trail must record the creation.
        $this->assertDatabaseHas('activity_logs', [
            'deal_id' => $deal->id,
            'action' => 'deal.created',
        ]);
    }

    public function test_j4u_can_finalize_a_deal_and_materials_are_generated(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $materialItem = Item::factory()->withMaterial()->create();
        $plainItem = Item::factory()->withoutMaterial()->create();

        $deal = Deal::factory()->create();
        $deal->items()->attach([$materialItem->id, $plainItem->id]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('finalize');

        $this->assertSame(DealStatus::Finalized, $deal->fresh()->status);

        $deadlines = $deal->fresh()->materialDeadlines;
        $this->assertCount(1, $deadlines);
        $this->assertSame($materialItem->id, $deadlines->first()->item_id);
        $this->assertSame($materialItem->name, $deadlines->first()->material_name);
    }

    public function test_j4u_can_mark_a_payment_term_paid(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $deal = Deal::factory()->create();
        $term = PaymentTerm::factory()->create([
            'deal_id' => $deal->id,
            'status' => PaymentStatus::Pending,
        ]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('markPaymentPaid', $term->id);

        $this->assertSame(PaymentStatus::Paid, $term->fresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'deal_id' => $deal->id,
            'action' => 'payment_term.updated',
        ]);
    }

    public function test_j4u_can_mark_a_material_deadline_received(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $deal = Deal::factory()->create();
        $deadline = MaterialDeadline::factory()->create(['deal_id' => $deal->id]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('markMaterialReceived', $deadline->id);

        $this->assertSame(MaterialStatus::Received, $deadline->fresh()->status);
        $this->assertNotNull($deadline->fresh()->received_at);
    }
}
