<?php

namespace Tests\Feature;

use App\Livewire\DealForm;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->j4u()->create());
    }

    public function test_a_draft_is_only_kept_for_new_deals(): void
    {
        $this->assertTrue(Livewire::test(DealForm::class)->instance()->keepsDraft());

        $deal = Deal::factory()->create();
        $this->assertFalse(Livewire::test(DealForm::class, ['deal' => $deal])->instance()->keepsDraft());
    }

    public function test_a_saved_draft_is_restored_field_by_field(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create(['default_price_idr' => 90_500_000]);
        $package = Package::factory()->create();

        Livewire::test(DealForm::class)
            ->call('restoreDraft', [
                'doctorId' => $doctor->id,
                'companyName' => 'PT Pulih',
                'brandName' => 'Pulih Brand',
                'picName' => 'Sari',
                'picContact' => '+62 812 0000 0000',
                'packageId' => $package->id,
                'currency' => 'USD',
                'inclusion' => 'Teks inclusion.',
                'finalPrice' => '12345',
                'currentStep' => 3,
                'items' => [
                    ['item_id' => $item->id, 'checked' => true, 'quantity' => 4, 'custom_price' => '777'],
                ],
                'paymentTerms' => [
                    ['description' => 'Termin 1', 'due_date' => '2027-01-15', 'amount' => '12345', 'notes' => 'DP'],
                ],
            ])
            ->assertSet('doctorId', $doctor->id)
            ->assertSet('companyName', 'PT Pulih')
            ->assertSet('brandName', 'Pulih Brand')
            ->assertSet('picName', 'Sari')
            ->assertSet('picContact', '+62 812 0000 0000')
            ->assertSet('packageId', $package->id)
            ->assertSet('currency', 'USD')
            ->assertSet('inclusion', 'Teks inclusion.')
            ->assertSet('finalPrice', '12345')
            ->assertSet('currentStep', 3)
            ->assertSet('draftRestored', true)
            ->assertSet('paymentTerms', fn (array $terms): bool => count($terms) === 1
                && $terms[0]['description'] === 'Termin 1'
                && $terms[0]['notes'] === 'DP')
            ->assertSet('items', fn (array $items): bool => collect($items)
                ->firstWhere('item_id', $item->id)['quantity'] === 4);
    }

    public function test_an_edit_ignores_a_draft_entirely(): void
    {
        $deal = Deal::factory()->create();

        Livewire::test(DealForm::class, ['deal' => $deal])
            ->call('restoreDraft', ['companyName' => 'PT Jangan Timpa'])
            ->assertSet('companyName', $deal->sponsor->company_name)
            ->assertSet('draftRestored', false);
    }

    public function test_a_draft_naming_things_that_no_longer_exist_is_ignored_gracefully(): void
    {
        Item::factory()->create();

        Livewire::test(DealForm::class)
            ->call('restoreDraft', [
                'doctorId' => 999999,
                'packageId' => 999999,
                'currency' => 'GBP',
                'items' => [['item_id' => 999999, 'checked' => true, 'quantity' => 3]],
                'companyName' => 'PT Tetap',
            ])
            ->assertSet('doctorId', 0)
            ->assertSet('packageId', null)
            ->assertSet('currency', 'IDR')
            ->assertSet('companyName', 'PT Tetap')
            ->assertHasNoErrors();
    }

    public function test_a_malformed_draft_does_not_break_the_form(): void
    {
        Item::factory()->create();

        Livewire::test(DealForm::class)
            ->call('restoreDraft', [
                'items' => 'not-an-array',
                'paymentTerms' => ['nonsense', 42],
                'currentStep' => 99,
            ])
            ->assertSet('currentStep', DealForm::TOTAL_STEPS)
            ->assertSet('paymentTerms', fn (array $terms): bool => count($terms) === 1)
            ->assertHasNoErrors();
    }

    public function test_discarding_a_draft_empties_the_form_and_tells_the_browser(): void
    {
        $doctor = User::factory()->doctor()->create();
        Item::factory()->create();

        Livewire::test(DealForm::class)
            ->call('restoreDraft', [
                'doctorId' => $doctor->id,
                'companyName' => 'PT Buang',
                'currentStep' => 2,
            ])
            ->assertSet('companyName', 'PT Buang')
            ->call('discardDraft')
            ->assertSet('companyName', '')
            ->assertSet('doctorId', 0)
            ->assertSet('currentStep', 1)
            ->assertSet('draftRestored', false)
            ->assertDispatched('deal-draft-cleared');
    }

    public function test_saving_a_deal_tells_the_browser_to_drop_the_draft(): void
    {
        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Simpan')
            ->set('brandName', 'Simpan Brand')
            ->set('picName', 'Sari')
            ->set('picContact', '0812')
            ->set('packageId', null)
            ->set('finalPrice', '1000')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'quantity' => 1,
                    'catalog_inclusion' => '', 'is_addon' => true, 'checked' => true, 'custom_price' => '1000'],
            ])
            ->set('paymentTerms', [
                ['id' => null, 'description' => 'Lunas', 'due_date' => '2027-01-15', 'amount' => '1000', 'notes' => ''],
            ])
            ->call('save')
            ->assertDispatched('deal-draft-cleared');
    }

    public function test_the_draft_keeper_is_mounted_only_on_the_create_form(): void
    {
        $html = Livewire::test(DealForm::class)->html();
        $this->assertStringContainsString('dealDraft(', $html);
        $this->assertStringContainsString('enabled: true', $html);

        $deal = Deal::factory()->create();
        $editHtml = Livewire::test(DealForm::class, ['deal' => $deal])->html();
        $this->assertStringContainsString('enabled: false', $editHtml);
    }
}
