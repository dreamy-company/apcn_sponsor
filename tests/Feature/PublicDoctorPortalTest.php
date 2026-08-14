<?php

namespace Tests\Feature;

use App\Livewire\Public\DoctorPortal;
use App\Models\Deal;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicDoctorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_token_returns_404(): void
    {
        $this->get(route('public.doctor', 'nope-not-real'))->assertNotFound();
    }

    public function test_portal_is_locked_until_the_correct_code_is_entered(): void
    {
        Setting::set('public_access_code', 'SECRET1');
        $doctor = User::factory()->doctor()->create();

        // Guests can reach the page (it renders the code gate).
        $this->get(route('public.doctor', $doctor->public_token))
            ->assertOk()
            ->assertSee('Enter access code');
    }

    public function test_wrong_code_keeps_it_locked(): void
    {
        Setting::set('public_access_code', 'SECRET1');
        $doctor = User::factory()->doctor()->create();

        Livewire::test(DoctorPortal::class, ['token' => $doctor->public_token])
            ->set('code', 'WRONG')
            ->call('unlock')
            ->assertHasErrors('code')
            ->assertSet('unlocked', false);
    }

    public function test_correct_code_unlocks_and_shows_only_that_doctors_deals(): void
    {
        Setting::set('public_access_code', 'SECRET1');

        $doctor = User::factory()->doctor()->create();
        $other = User::factory()->doctor()->create();

        $mine = Deal::factory()->create(['doctor_id' => $doctor->id]);
        $theirs = Deal::factory()->create(['doctor_id' => $other->id]);

        Livewire::test(DoctorPortal::class, ['token' => $doctor->public_token])
            ->set('code', 'SECRET1')
            ->call('unlock')
            ->assertHasNoErrors()
            ->assertSet('unlocked', true)
            ->assertSee($mine->sponsor->company_name)
            ->assertDontSee($theirs->sponsor->company_name);
    }
}
