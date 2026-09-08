<?php

namespace Tests\Feature;

use App\Actions\Deal\FinalizeDealAction;
use App\Enums\DealStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\UnbalancedPaymentTermsException;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTermBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->j4u()->create());
    }

    public function test_finalizing_is_blocked_when_terms_do_not_match_the_final_price(): void
    {
        $deal = Deal::factory()->create(['final_price' => 100_000_000]);
        PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 60_000_000]);

        $this->expectException(UnbalancedPaymentTermsException::class);

        app(FinalizeDealAction::class)->execute($deal);
    }

    public function test_finalizing_succeeds_when_terms_sum_to_the_final_price(): void
    {
        $deal = Deal::factory()->create(['final_price' => 100_000_000]);
        PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 60_000_000]);
        PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 40_000_000]);

        app(FinalizeDealAction::class)->execute($deal->fresh());

        $this->assertSame(DealStatus::Finalized, $deal->fresh()->status);
    }

    public function test_the_unbalanced_error_surfaces_on_the_deal_page(): void
    {
        $deal = Deal::factory()->create(['final_price' => 100_000_000]);
        PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 1_000]);

        Livewire::test(DealShow::class, ['deal' => $deal])->call('finalize');

        $this->assertSame(DealStatus::Draft, $deal->fresh()->status);
    }

    public function test_a_draft_may_be_saved_unbalanced(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Draf')
            ->set('brandName', 'Draf Brand')
            ->set('picName', 'Ani')
            ->set('picContact', '0812')
            ->set('packageId', null)
            ->set('finalPrice', '100000000')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'quantity' => 1, 'catalog_inclusion' => '', 'is_addon' => true, 'checked' => true, 'custom_price' => ''],
            ])
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'DP', 'due_date' => '2027-01-15', 'amount' => '10000000', 'notes' => ''],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('deals', ['final_price' => '100000000.00', 'status' => DealStatus::Draft->value]);
    }

    public function test_balance_term_assigns_the_remaining_amount(): void
    {
        $component = Livewire::test(DealForm::class)
            ->set('finalPrice', '100000000')
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'DP', 'due_date' => '2027-01-15', 'amount' => '30000000', 'notes' => ''],
                ['id' => null, 'description' => 'Pelunasan', 'due_date' => '2027-06-15', 'amount' => '0', 'notes' => ''],
            ])
            ->call('balanceTerm', 1);

        $this->assertSame('70000000', $component->get('paymentTerms')[1]['amount']);
        $this->assertTrue($component->instance()->termsBalanced());
    }

    public function test_editing_a_deal_preserves_a_paid_term_status_and_proof(): void
    {
        Storage::fake('public');

        $item = Item::factory()->create();
        $deal = Deal::factory()->create(['final_price' => 100_000_000, 'package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true]);

        $term = PaymentTerm::factory()->create([
            'deal_id' => $deal->id,
            'description' => 'DP',
            'amount' => 100_000_000,
            'status' => PaymentStatus::Pending,
        ]);

        // Mark it paid and attach a transfer proof.
        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('markPaymentPaid', $term->id)
            ->set("proofUploads.{$term->id}", UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadProof', $term->id);

        $term->refresh();
        $this->assertSame(PaymentStatus::Paid, $term->status);
        $this->assertTrue($term->hasProof());
        $proofPath = $term->proof_path;

        // Now edit the deal, keeping that term (carrying its id) and adding notes.
        Livewire::test(DealForm::class, ['deal' => $deal])
            ->set('paymentTerms', [
                ['id' => $term->id, 'description' => 'DP (revised)', 'due_date' => '2027-02-01', 'amount' => '100000000', 'notes' => 'Confirmed by finance'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $term->refresh();
        $this->assertSame('DP (revised)', $term->description);
        $this->assertSame('Confirmed by finance', $term->notes);
        // The regression this guards: status and proof must survive the edit.
        $this->assertSame(PaymentStatus::Paid, $term->status);
        $this->assertSame($proofPath, $term->proof_path);
        $this->assertSame(1, $deal->fresh()->paymentTerms()->count());
    }

    public function test_a_removed_term_is_deleted_on_edit(): void
    {
        $item = Item::factory()->create();
        $deal = Deal::factory()->create(['package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true]);

        $keep = PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 1_000]);
        $drop = PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 2_000]);

        Livewire::test(DealForm::class, ['deal' => $deal])
            ->set('paymentTerms', [
                ['id' => $keep->id, 'description' => 'Kept', 'due_date' => '2027-02-01', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('save');

        $this->assertDatabaseHas('payment_terms', ['id' => $keep->id]);
        $this->assertDatabaseMissing('payment_terms', ['id' => $drop->id]);
    }

    public function test_verifying_a_term_requires_a_proof_and_records_the_actor(): void
    {
        Storage::fake('public');

        $admin = auth()->user();
        $deal = Deal::factory()->create();
        $term = PaymentTerm::factory()->create(['deal_id' => $deal->id]);

        $component = Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('verifyPaymentTerm', $term->id);

        $this->assertFalse($term->fresh()->isVerified());

        $component->set("proofUploads.{$term->id}", UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadProof', $term->id)
            ->call('verifyPaymentTerm', $term->id);

        $term->refresh();
        $this->assertTrue($term->isVerified());
        $this->assertSame($admin->id, $term->verified_by_id);

        $component->call('verifyPaymentTerm', $term->id, false);
        $this->assertFalse($term->fresh()->isVerified());
    }
}
