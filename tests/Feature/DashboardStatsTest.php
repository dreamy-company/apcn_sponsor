<?php

namespace Tests\Feature;

use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\MaterialDeadline;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_j4u_dashboard_shows_global_figures(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        $doctorA = User::factory()->doctor()->create();
        $doctorB = User::factory()->doctor()->create();

        $dealA = Deal::factory()->finalized()->create(['doctor_id' => $doctorA->id, 'final_price' => 100_000_000]);
        $dealB = Deal::factory()->finalized()->create(['doctor_id' => $doctorB->id, 'final_price' => 200_000_000]);
        Deal::factory()->create(['doctor_id' => $doctorA->id, 'final_price' => 50_000_000]);

        PaymentTerm::factory()->create(['deal_id' => $dealA->id, 'amount' => 100_000_000, 'status' => PaymentStatus::Paid]);
        PaymentTerm::factory()->create(['deal_id' => $dealB->id, 'amount' => 200_000_000, 'status' => PaymentStatus::Pending]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($dealA->deal_number)
            ->assertSee($dealB->deal_number)
            // Total committed: 300M (finalized only)
            ->assertSee('300.000.000');
    }

    public function test_dashboard_shows_payment_and_material_progress(): void
    {
        $j4u = User::factory()->j4u()->create();
        $this->actingAs($j4u);

        $deal = Deal::factory()->finalized()->create();

        PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 100_000_000, 'status' => PaymentStatus::Paid]);
        PaymentTerm::factory()->create(['deal_id' => $deal->id, 'amount' => 50_000_000, 'status' => PaymentStatus::Pending]);

        MaterialDeadline::factory()->create(['deal_id' => $deal->id, 'status' => MaterialStatus::Received]);
        MaterialDeadline::factory()->create(['deal_id' => $deal->id, 'status' => MaterialStatus::Pending]);

        $this->get(route('dashboard'))
            ->assertOk()
            // paid 100M, outstanding 50M
            ->assertSee('100.000.000')
            ->assertSee('50.000.000')
            // material progress 1 / 2
            ->assertSee('1', false)
            ->assertSee('/ 2', false);
    }

    public function test_dashboard_renders_with_zero_deals(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No deals yet.');
    }
}
