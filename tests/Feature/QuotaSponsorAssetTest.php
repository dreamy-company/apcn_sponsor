<?php

namespace Tests\Feature;

use App\Actions\Deal\CreateDealAction;
use App\Actions\Deal\FinalizeDealAction;
use App\Actions\Deal\StoreDealAssetAction;
use App\DTOs\Deal\DealData;
use App\Exceptions\QuotaExceededException;
use App\Livewire\Catalog\CatalogItemForm;
use App\Livewire\Catalog\CatalogItemShow;
use App\Livewire\Catalog\CatalogPackageForm;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Livewire\Sponsors\SponsorIndex;
use App\Livewire\Sponsors\SponsorShow;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QuotaSponsorAssetTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsJ4u(): User
    {
        $user = User::factory()->j4u()->create();
        $this->actingAs($user);

        return $user;
    }

    // ---- Quota ---------------------------------------------------------------

    public function test_item_quota_persists_through_the_form(): void
    {
        $this->actingAsJ4u();

        Livewire::test(CatalogItemForm::class)
            ->set('name', 'Booth 6x6m')
            ->set('type', 'booth')
            ->set('quota', 5)
            ->call('save');

        $this->assertDatabaseHas('items', ['name' => 'Booth 6x6m', 'quota' => 5]);
    }

    public function test_package_quota_persists_and_blank_means_unlimited(): void
    {
        $this->actingAsJ4u();

        Livewire::test(CatalogPackageForm::class)
            ->set('name', 'Diamond')
            ->set('defaultPrice', '500000000')
            ->set('quota', null)
            ->call('save');

        $this->assertDatabaseHas('packages', ['name' => 'Diamond', 'quota' => null]);
    }

    public function test_finalizing_is_blocked_when_item_quota_is_reached(): void
    {
        $this->actingAsJ4u();

        $item = Item::factory()->create(['quota' => 1]);

        // First sponsor consumes the only slot.
        $taken = Deal::factory()->finalized()->create(['package_id' => null]);
        $taken->items()->attach($item->id, ['is_addon' => true]);

        // A second draft holds the same item; finalizing must fail.
        $draft = Deal::factory()->create(['package_id' => null]);
        $draft->items()->attach($item->id, ['is_addon' => true]);

        $this->expectException(QuotaExceededException::class);

        app(FinalizeDealAction::class)->execute($draft);
    }

    public function test_finalizing_succeeds_within_quota(): void
    {
        $this->actingAsJ4u();

        $item = Item::factory()->create(['quota' => 2]);

        $taken = Deal::factory()->finalized()->create(['package_id' => null]);
        $taken->items()->attach($item->id, ['is_addon' => true]);

        $draft = Deal::factory()->create(['package_id' => null]);
        $draft->items()->attach($item->id, ['is_addon' => true]);

        app(FinalizeDealAction::class)->execute($draft->fresh());

        $this->assertSame('finalized', $draft->fresh()->status->value);
    }

    public function test_wizard_blocks_selecting_a_full_item(): void
    {
        $this->actingAsJ4u();

        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create(['quota' => 1]);

        // Item already fully consumed by a finalized deal.
        $full = Deal::factory()->finalized()->create(['package_id' => null]);
        $full->items()->attach($item->id, ['is_addon' => true]);

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Baru')
            ->set('picName', 'Andi')
            ->set('picContact', '+62 811 1111 1111')
            ->set('packageId', null)
            ->set('finalPrice', '10000000')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => 1, 'is_addon' => true, 'checked' => true, 'custom_price' => ''],
            ])
            ->set('paymentTerms', [])
            ->call('save')
            ->assertHasErrors('items');

        $this->assertDatabaseMissing('sponsors', ['company_name' => 'PT Baru']);
    }

    // ---- Sponsor dedupe ------------------------------------------------------

    public function test_deals_with_the_same_company_share_one_sponsor(): void
    {
        $this->actingAsJ4u();

        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        $make = fn (): DealData => new DealData(
            doctorId: $doctor->id,
            companyName: 'PT Sehat Jaya',
            picName: 'Rina',
            picContact: '+62 812 0000 0000',
            packageId: null,
            finalPrice: '10000000',
            items: [['item_id' => $item->id, 'is_addon' => true, 'custom_price' => null]],
            paymentTerms: [],
        );

        app(CreateDealAction::class)->execute($make());
        app(CreateDealAction::class)->execute($make());

        $this->assertDatabaseCount('sponsors', 1);
        $this->assertSame(2, Sponsor::firstWhere('company_name', 'PT Sehat Jaya')->deals()->count());
    }

    // ---- Sponsor pages -------------------------------------------------------

    public function test_sponsor_index_and_show_render(): void
    {
        $this->actingAsJ4u();

        $sponsor = Sponsor::factory()->create(['company_name' => 'PT Contoh']);
        Deal::factory()->create(['sponsor_id' => $sponsor->id]);

        Livewire::test(SponsorIndex::class)->assertOk()->assertSee('PT Contoh');
        Livewire::test(SponsorShow::class, ['sponsor' => $sponsor])->assertOk()->assertSee('PT Contoh');
    }

    public function test_sponsor_top_package_is_the_highest_priced_tier(): void
    {
        $sponsor = Sponsor::factory()->create();
        $silver = Package::factory()->create(['name' => 'Silver', 'default_price' => 40_000_000]);
        $diamond = Package::factory()->create(['name' => 'Diamond', 'default_price' => 500_000_000]);

        Deal::factory()->create(['sponsor_id' => $sponsor->id, 'package_id' => $silver->id]);
        Deal::factory()->create(['sponsor_id' => $sponsor->id, 'package_id' => $diamond->id]);

        $sponsor->load('deals.package');

        $this->assertSame($diamond->id, $sponsor->topPackage()?->id);
    }

    public function test_item_show_lists_sponsors_that_took_it(): void
    {
        $this->actingAsJ4u();

        $item = Item::factory()->create();
        $sponsor = Sponsor::factory()->create(['company_name' => 'PT Ambil Item']);
        $deal = Deal::factory()->create(['sponsor_id' => $sponsor->id, 'package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true]);

        Livewire::test(CatalogItemShow::class, ['item' => $item])
            ->assertOk()
            ->assertSee('PT Ambil Item');
    }

    // ---- Assets --------------------------------------------------------------

    public function test_admin_can_upload_a_large_asset_in_the_wizard(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $doctor = User::factory()->doctor()->create();
        $item = Item::factory()->create();

        Livewire::test(DealForm::class)
            ->set('doctorId', $doctor->id)
            ->set('companyName', 'PT Asset')
            ->set('picName', 'Sari')
            ->set('picContact', '+62 813 2222 2222')
            ->set('packageId', null)
            ->set('finalPrice', '10000000')
            ->set('items', [
                ['item_id' => $item->id, 'name' => $item->name, 'type' => null, 'quota' => null, 'is_addon' => true, 'checked' => true, 'custom_price' => ''],
            ])
            ->set('paymentTerms', [])
            ->set('assets', [UploadedFile::fake()->create('contract.pdf', 30000)]) // ~30MB > 20MB
            ->call('save');

        $deal = Deal::whereHas('sponsor', fn ($q) => $q->where('company_name', 'PT Asset'))->firstOrFail();

        $this->assertSame(1, $deal->assets()->count());
        Storage::disk('public')->assertExists($deal->assets()->first()->path);
    }

    public function test_oversized_asset_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        // A ~60MB file exceeds the 50MB cap and is rejected by Livewire's
        // temporary-upload validation (config/livewire.php rules max:51200).
        Livewire::test(DealForm::class)
            ->set('assets', [UploadedFile::fake()->create('huge.pdf', 60000)])
            ->assertHasErrors('assets.0');
    }

    public function test_admin_can_upload_and_delete_assets_on_the_sponsor_page(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $sponsor = Sponsor::factory()->create();
        $deal = Deal::factory()->create(['sponsor_id' => $sponsor->id]);

        Livewire::test(SponsorShow::class, ['sponsor' => $sponsor])
            ->set("dealAssets.{$deal->id}", [UploadedFile::fake()->create('brief.pdf', 25000)])
            ->call('uploadAssets', $deal->id);

        $asset = $deal->assets()->firstOrFail();
        Storage::disk('public')->assertExists($asset->path);

        Livewire::test(SponsorShow::class, ['sponsor' => $sponsor])
            ->call('deleteAsset', $asset->id);

        $this->assertSame(0, $deal->assets()->count());
        Storage::disk('public')->assertMissing($asset->path);
    }

    public function test_asset_can_be_given_a_name_on_upload(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $sponsor = Sponsor::factory()->create();
        $deal = Deal::factory()->create(['sponsor_id' => $sponsor->id]);

        Livewire::test(SponsorShow::class, ['sponsor' => $sponsor])
            ->set("dealAssets.{$deal->id}", [UploadedFile::fake()->create('brief.pdf', 2000)])
            ->set("dealAssetNames.{$deal->id}", ['Kontrak Utama'])
            ->call('uploadAssets', $deal->id);

        $asset = $deal->assets()->firstOrFail();

        $this->assertSame('Kontrak Utama', $asset->name);
        $this->assertSame('Kontrak Utama', $asset->displayName());
        $this->assertSame('Kontrak Utama.pdf', $asset->downloadName());
    }

    public function test_admin_can_download_a_single_asset_with_its_friendly_name(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $deal = Deal::factory()->create();
        $asset = app(StoreDealAssetAction::class)->execute(
            $deal, UploadedFile::fake()->create('brief.pdf', 1000), null, 'Kontrak'
        );

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('downloadAsset', $asset->id)
            ->assertFileDownloaded('Kontrak.pdf');
    }

    public function test_admin_can_download_all_assets_as_a_zip(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $deal = Deal::factory()->create();
        app(StoreDealAssetAction::class)->execute($deal, UploadedFile::fake()->create('a.pdf', 500));
        app(StoreDealAssetAction::class)->execute($deal, UploadedFile::fake()->create('b.pdf', 500));

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('downloadAll')
            ->assertFileDownloaded($deal->deal_number.'-assets.zip');
    }

    public function test_download_all_with_no_assets_downloads_nothing(): void
    {
        Storage::fake('public');
        $this->actingAsJ4u();

        $deal = Deal::factory()->create();

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('downloadAll')
            ->assertNoFileDownloaded();
    }
}
