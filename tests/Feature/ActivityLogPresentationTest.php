<?php

namespace Tests\Feature;

use App\Livewire\DealShow;
use App\Models\ActivityLog;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogPresentationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function describe(string $action, ?array $details = null): string
    {
        $log = new ActivityLog(['deal_id' => 1, 'action' => $action, 'details' => $details]);

        return $log->describe();
    }

    public function test_every_known_action_reads_as_a_plain_sentence(): void
    {
        $cases = [
            'deal.created' => 'membuat deal ini',
            'payment_term.proof_removed' => 'menghapus bukti transfer',
            'guarantee_letter.verified' => 'memverifikasi pembayaran surat jaminan',
            'guarantee_letter.unverified' => 'membatalkan verifikasi surat jaminan',
        ];

        foreach ($cases as $action => $expected) {
            $this->assertSame($expected, $this->describe($action));
        }
    }

    public function test_a_status_change_is_spelled_out_rather_than_shown_as_a_slug(): void
    {
        $this->assertSame(
            'memfinalkan deal ini',
            $this->describe('deal.updated', ['status' => ['old' => 'draft', 'new' => 'finalized']])
        );

        $this->assertSame(
            'menandai termin pembayaran sebagai lunas',
            $this->describe('payment_term.updated', ['status' => ['old' => 'pending', 'new' => 'paid']])
        );

        $this->assertSame(
            'menandai materi sudah diterima',
            $this->describe('material_deadline.updated', ['status' => ['old' => 'pending', 'new' => 'received']])
        );
    }

    public function test_changed_fields_are_named_in_indonesian(): void
    {
        $this->assertSame(
            'memperbarui harga akhir dan mata uang pada deal ini',
            $this->describe('deal.updated', [
                'final_price' => ['old' => '1', 'new' => '2'],
                'currency' => ['old' => 'IDR', 'new' => 'USD'],
            ])
        );
    }

    public function test_file_names_are_included_where_they_help(): void
    {
        $this->assertSame(
            'mengunggah bukti transfer (bukti.jpg)',
            $this->describe('payment_term.proof_uploaded', ['file' => ['old' => null, 'new' => 'bukti.jpg']])
        );

        $this->assertSame(
            'menjadwalkan pembayaran surat jaminan pada 01 Mar 2027',
            $this->describe('guarantee_letter.scheduled', [
                'payment_due_date' => ['old' => null, 'new' => '2027-03-01'],
            ])
        );
    }

    public function test_a_flat_attribute_snapshot_is_handled(): void
    {
        // The three *.created actions store raw attributes, not {old,new} pairs.
        $this->assertSame(
            'menambahkan termin pembayaran "Termin 1" sebesar Rp 50.000.000',
            $this->describe('payment_term.created', [
                'deal_id' => 1,
                'description' => 'Termin 1',
                'amount' => 50000000,
                'status' => 'pending',
            ])
        );
    }

    public function test_null_details_and_unknown_actions_never_blow_up(): void
    {
        $this->assertNotSame('', $this->describe('deal.updated', null));
        $this->assertNotSame('', $this->describe('payment_term.updated', []));
        $this->assertStringContainsString('something new', $this->describe('something.new'));
    }

    public function test_the_deal_page_shows_no_raw_slugs(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $deal = Deal::factory()->create();
        $deal->update(['final_price' => 123_456]);

        $html = Livewire::test(DealShow::class, ['deal' => $deal])->assertOk()->html();

        // The activity feed must not leak dotted machine slugs to the reader.
        foreach (['deal.created', 'deal.updated', 'payment_term.created'] as $slug) {
            $this->assertFalse(Str::contains($html, $slug), "Raw slug [$slug] leaked into the page.");
        }

        $this->assertStringContainsString('membuat deal ini', $html);
    }
}
