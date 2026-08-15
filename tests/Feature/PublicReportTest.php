<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Public\SponsorReport;
use App\Livewire\Public\SponsorshipReport;
use App\Models\Deal;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_token_returns_404(): void
    {
        Setting::set('report_public_token', 'valid-token');

        $this->get(route('public.report', 'nope-not-real'))->assertNotFound();
    }

    public function test_report_is_locked_until_the_correct_code_is_entered(): void
    {
        Setting::set('report_public_token', 'valid-token');
        Setting::set('public_access_code', 'SECRET1');

        $this->get(route('public.report', 'valid-token'))
            ->assertOk()
            ->assertSee('Enter access code');
    }

    public function test_wrong_code_keeps_it_locked(): void
    {
        Setting::set('report_public_token', 'valid-token');
        Setting::set('public_access_code', 'SECRET1');

        Livewire::test(SponsorshipReport::class, ['token' => 'valid-token'])
            ->set('code', 'WRONG')
            ->call('unlock')
            ->assertHasErrors('code')
            ->assertSet('unlocked', false);
    }

    public function test_correct_code_unlocks_and_shows_committed_sponsors(): void
    {
        Setting::set('report_public_token', 'valid-token');
        Setting::set('public_access_code', 'SECRET1');

        $deal = Deal::factory()->finalized()->create(['final_price' => 550_000_000]);

        Livewire::test(SponsorshipReport::class, ['token' => 'valid-token'])
            ->set('code', 'SECRET1')
            ->call('unlock')
            ->assertHasNoErrors()
            ->assertSet('unlocked', true)
            ->assertSee($deal->sponsor->company_name)
            ->assertSee('Committed Sponsors');
    }

    public function test_report_lists_sponsors_that_have_dealt(): void
    {
        Setting::set('report_public_token', 'valid-token');
        Setting::set('public_access_code', 'SECRET1');

        $deal = Deal::factory()->finalized()->create();

        Livewire::test(SponsorshipReport::class, ['token' => 'valid-token'])
            ->set('code', 'SECRET1')
            ->call('unlock')
            ->assertSee($deal->sponsor->company_name)
            ->assertSee('Sponsors');
    }

    public function test_public_sponsor_detail_bad_token_404s(): void
    {
        Setting::set('report_public_token', 'valid-token');
        $sponsor = Sponsor::factory()->create();

        $this->get(route('public.report.sponsor', ['token' => 'wrong', 'sponsor' => $sponsor]))->assertNotFound();
    }

    public function test_public_sponsor_detail_unlocks_and_shows_deals(): void
    {
        Setting::set('report_public_token', 'valid-token');
        Setting::set('public_access_code', 'SECRET1');

        $sponsor = Sponsor::factory()->create();
        $deal = Deal::factory()->create(['sponsor_id' => $sponsor->id]);

        Livewire::test(SponsorReport::class, ['token' => 'valid-token', 'sponsor' => $sponsor])
            ->assertSee('Enter access code')
            ->set('code', 'SECRET1')
            ->call('unlock')
            ->assertHasNoErrors()
            ->assertSet('unlocked', true)
            ->assertSee($deal->deal_number);
    }

    public function test_dashboard_provides_a_report_link_and_can_regenerate_it(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        // mount() lazily generates a token.
        Livewire::test(Dashboard::class)->assertOk();
        $first = Setting::get('report_public_token');
        $this->assertNotNull($first);

        Livewire::test(Dashboard::class)
            ->call('regenerateReportLink')
            ->assertHasNoErrors();

        $this->assertNotSame($first, Setting::get('report_public_token'));
    }

    public function test_admin_can_set_the_access_code_from_the_dashboard(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(Dashboard::class)
            ->set('accessCode', 'NEWCODE9')
            ->call('saveAccessCode')
            ->assertHasNoErrors();

        $this->assertSame('NEWCODE9', Setting::get('public_access_code'));
    }

    public function test_access_code_requires_a_minimum_length(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        Livewire::test(Dashboard::class)
            ->set('accessCode', 'ab')
            ->call('saveAccessCode')
            ->assertHasErrors('accessCode');
    }
}
