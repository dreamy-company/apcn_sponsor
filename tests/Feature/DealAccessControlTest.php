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

    public function test_doctor_cannot_access_the_admin_app(): void
    {
        // Doctors are non-login users; even if resolved into the session they
        // are barred from every admin surface.
        $doctor = User::factory()->doctor()->create();
        $this->actingAs($doctor);

        $deal = Deal::factory()->create(['doctor_id' => $doctor->id]);

        $this->get(route('deals.index'))->assertForbidden();
        $this->get(route('deals.show', $deal))->assertForbidden();
        $this->get(route('deals.create'))->assertForbidden();
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

    public function test_j4u_sees_all_deals_in_the_list(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $a = Deal::factory()->create();
        $b = Deal::factory()->create();

        $this->get(route('deals.index'))
            ->assertOk()
            ->assertSee($a->deal_number)
            ->assertSee($b->deal_number);
    }

    public function test_j4u_can_view_any_deal(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $deal = Deal::factory()->create();

        $this->get(route('deals.show', $deal))->assertOk();
    }
}
