<?php

namespace Tests\Feature;

use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsJ4u(): void
    {
        $this->actingAs(User::factory()->j4u()->create());
    }

    private function termOnFreshDeal(): PaymentTerm
    {
        $deal = Deal::factory()->create();

        return PaymentTerm::factory()->create(['deal_id' => $deal->id]);
    }

    public function test_admin_can_upload_a_transfer_proof(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $term = $this->termOnFreshDeal();

        Livewire::test(DealShow::class, ['deal' => $term->deal])
            ->set("proofUploads.{$term->id}", UploadedFile::fake()->image('bukti.png'))
            ->call('uploadProof', $term->id)
            ->assertHasNoErrors();

        $term->refresh();

        $this->assertTrue($term->hasProof());
        Storage::disk('public')->assertExists($term->proof_path);
        $this->assertDatabaseHas('activity_logs', [
            'deal_id' => $term->deal_id,
            'action' => 'payment_term.proof_uploaded',
        ]);
    }

    public function test_admin_can_download_the_proof_with_a_friendly_name(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $term = $this->termOnFreshDeal();

        Livewire::test(DealShow::class, ['deal' => $term->deal])
            ->set("proofUploads.{$term->id}", UploadedFile::fake()->create('transfer.pdf', 500, 'application/pdf'))
            ->call('uploadProof', $term->id);

        $term->refresh();

        Livewire::test(DealShow::class, ['deal' => $term->deal])
            ->call('downloadProof', $term->id)
            ->assertFileDownloaded($term->proofDownloadName());
    }

    public function test_admin_can_delete_the_proof(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $term = $this->termOnFreshDeal();

        Livewire::test(DealShow::class, ['deal' => $term->deal])
            ->set("proofUploads.{$term->id}", UploadedFile::fake()->image('bukti.png'))
            ->call('uploadProof', $term->id);

        $path = $term->refresh()->proof_path;

        Livewire::test(DealShow::class, ['deal' => $term->deal])
            ->call('deleteProof', $term->id);

        $term->refresh();

        $this->assertFalse($term->hasProof());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $term = $this->termOnFreshDeal();

        Livewire::test(DealShow::class, ['deal' => $term->deal])
            ->set("proofUploads.{$term->id}", UploadedFile::fake()->create('malware.exe', 10))
            ->call('uploadProof', $term->id)
            ->assertHasErrors("proofUploads.{$term->id}");

        $this->assertFalse($term->refresh()->hasProof());
    }
}
