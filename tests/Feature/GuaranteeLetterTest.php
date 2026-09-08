<?php

namespace Tests\Feature;

use App\Actions\Deal\FinalizeDealAction;
use App\Enums\DealStatus;
use App\Enums\GuaranteeLetterStatus;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GuaranteeLetterTest extends TestCase
{
    use RefreshDatabase;

    private Deal $deal;

    private PaymentTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->j4u()->create());

        $this->deal = Deal::factory()->create(['final_price' => 100_000_000]);
        $this->term = PaymentTerm::factory()->create([
            'deal_id' => $this->deal->id,
            'description' => 'Termin 1',
            'amount' => 100_000_000,
        ]);
    }

    public function test_the_three_step_flow_settles_a_payment_term(): void
    {
        $component = Livewire::test(DealShow::class, ['deal' => $this->deal])
            // 1. the letter that will settle this term
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('surat-jaminan.pdf', 200, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id);

        $letter = $this->term->fresh()->guaranteeLetter;
        $this->assertNotNull($letter);
        $this->assertSame(GuaranteeLetterStatus::Uploaded, $letter->status);
        $this->assertTrue($letter->hasDocument());
        Storage::disk('public')->assertExists($letter->doc_path);

        // 2. payment date
        $component->set("glDueDates.{$this->term->id}", '2027-03-01')
            ->call('scheduleGuaranteeLetter', $this->term->id);

        $letter = $this->term->fresh()->guaranteeLetter;
        $this->assertSame(GuaranteeLetterStatus::Scheduled, $letter->status);
        $this->assertSame('2027-03-01', $letter->payment_due_date->toDateString());

        // 3. transfer proof on the day
        $component->set("glProofs.{$this->term->id}", UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadGuaranteeLetterProof', $this->term->id);

        $letter = $this->term->fresh()->guaranteeLetter;
        $this->assertSame(GuaranteeLetterStatus::Paid, $letter->status);
        $this->assertTrue($letter->hasProof());
        Storage::disk('public')->assertExists($letter->proof_path);
    }

    public function test_the_letter_belongs_to_the_term_and_carries_no_amount_of_its_own(): void
    {
        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id);

        $letter = $this->term->fresh()->guaranteeLetter;

        $this->assertSame($this->term->id, $letter->payment_term_id);
        $this->assertSame('100000000.00', $letter->paymentTerm->amount);
        $this->assertFalse(Schema::hasColumn('guarantee_letters', 'amount'));
    }

    public function test_saving_twice_replaces_the_document_rather_than_adding_a_second_letter(): void
    {
        $component = Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id);

        $first = $this->term->fresh()->guaranteeLetter->doc_path;

        $component->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id);

        $this->assertSame(1, $this->deal->paymentTerms()->first()->guaranteeLetter()->count());
        $this->assertSame('second.pdf', $this->term->fresh()->guaranteeLetter->doc_original_name);
        Storage::disk('public')->assertMissing($first);
    }

    public function test_a_term_settled_by_a_letter_still_counts_toward_the_balance_check(): void
    {
        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id);

        // The term alone matches the final price, so finalizing must succeed —
        // the letter adds no money of its own (BR-09).
        app(FinalizeDealAction::class)->execute($this->deal->fresh());

        $this->assertSame(DealStatus::Finalized, $this->deal->fresh()->status);
    }

    public function test_payment_progress_does_not_double_count_the_letter(): void
    {
        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id)
            ->assertViewHas('totalTerms', fn ($total): bool => (float) $total === 100_000_000.0);
    }

    public function test_scheduling_requires_a_letter_first(): void
    {
        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDueDates.{$this->term->id}", '2027-03-01')
            ->call('scheduleGuaranteeLetter', $this->term->id);

        $this->assertNull($this->term->fresh()->guaranteeLetter);
    }

    public function test_proof_requires_a_scheduled_payment_date(): void
    {
        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id)
            ->set("glProofs.{$this->term->id}", UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadGuaranteeLetterProof', $this->term->id);

        $letter = $this->term->fresh()->guaranteeLetter;
        $this->assertFalse($letter->hasProof());
        $this->assertSame(GuaranteeLetterStatus::Uploaded, $letter->status);
    }

    public function test_a_disallowed_document_type_is_rejected(): void
    {
        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('malware.exe', 10))
            ->call('saveGuaranteeLetter', $this->term->id)
            ->assertHasErrors("glDocuments.{$this->term->id}");

        $this->assertNull($this->term->fresh()->guaranteeLetter);
    }

    public function test_verification_requires_a_proof_and_records_the_actor(): void
    {
        $admin = auth()->user();

        $component = Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$this->term->id}", UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $this->term->id)
            ->call('verifyGuaranteeLetter', $this->term->id);

        $this->assertFalse($this->term->fresh()->guaranteeLetter->isVerified());

        $component->set("glDueDates.{$this->term->id}", '2027-03-01')
            ->call('scheduleGuaranteeLetter', $this->term->id)
            ->set("glProofs.{$this->term->id}", UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadGuaranteeLetterProof', $this->term->id)
            ->call('verifyGuaranteeLetter', $this->term->id);

        $letter = $this->term->fresh()->guaranteeLetter;
        $this->assertTrue($letter->isVerified());
        $this->assertSame($admin->id, $letter->verified_by_id);
    }

    public function test_a_term_from_another_deal_is_rejected(): void
    {
        $otherTerm = PaymentTerm::factory()->create();

        Livewire::test(DealShow::class, ['deal' => $this->deal])
            ->set("glDocuments.{$otherTerm->id}", UploadedFile::fake()->create('gl.pdf', 100, 'application/pdf'))
            ->call('saveGuaranteeLetter', $otherTerm->id)
            ->assertForbidden();
    }
}
