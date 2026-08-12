<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_deal_pages(): void
    {
        $this->get(route('deals.index'))->assertRedirect(route('login'));
        $this->get(route('deals.create'))->assertRedirect(route('login'));
    }

    public function test_doctor_cannot_access_deal_write_routes(): void
    {
        $doctor = User::factory()->doctor()->create();
        $this->actingAs($doctor);

        $this->get(route('deals.create'))->assertForbidden();

        $deal = Deal::factory()->create(['doctor_id' => $doctor->id]);
        $this->get(route('deals.edit', $deal))->assertForbidden();
    }

    public function test_j4u_can_access_deal_write_routes(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        $deal = Deal::factory()->create();

        $this->get(route('deals.create'))->assertOk();
        $this->get(route('deals.edit', $deal))->assertOk();
    }

    public function test_doctor_only_sees_own_deals_in_the_list(): void
    {
        $doctor = User::factory()->doctor()->create();
        $this->actingAs($doctor);

        $mine = Deal::factory()->create(['doctor_id' => $doctor->id]);
        $theirs = Deal::factory()->create();

        $this->get(route('deals.index'))
            ->assertOk()
            ->assertSee($mine->deal_number)
            ->assertDontSee($theirs->deal_number);
    }

    public function test_doctor_can_view_own_deal_but_not_another_doctors_deal(): void
    {
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();
        $this->actingAs($doctor);

        $mine = Deal::factory()->create(['doctor_id' => $doctor->id]);
        $theirs = Deal::factory()->create(['doctor_id' => $otherDoctor->id]);

        $this->get(route('deals.show', $mine))->assertOk();
        $this->get(route('deals.show', $theirs))->assertForbidden();
    }

    public function test_j4u_can_view_any_deal(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        $deal = Deal::factory()->create();

        $this->get(route('deals.show', $deal))->assertOk();
    }
}
