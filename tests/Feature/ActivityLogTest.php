<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\Deal;
use App\Models\Item;
use App\Models\PaymentTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_deal_creation_is_logged(): void
    {
        $user = User::factory()->j4u()->create();
        $this->actingAs($user);

        $deal = Deal::factory()->create();

        $log = ActivityLog::where('deal_id', $deal->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('deal.created', $log->action);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_finalizing_a_deal_is_logged_with_old_and_new_status(): void
    {
        $user = User::factory()->j4u()->create();
        $this->actingAs($user);

        $deal = Deal::factory()->create();
        $deal->update(['status' => DealStatus::Finalized]);

        $log = ActivityLog::where('deal_id', $deal->id)
            ->where('action', 'deal.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('draft', $log->details['status']['old']);
        $this->assertSame('finalized', $log->details['status']['new']);
    }

    public function test_updating_a_payment_term_is_logged(): void
    {
        $user = User::factory()->j4u()->create();
        $this->actingAs($user);

        $term = PaymentTerm::factory()->create(['status' => PaymentStatus::Pending]);

        $term->update(['status' => PaymentStatus::Paid]);

        $log = ActivityLog::where('deal_id', $term->deal_id)
            ->where('action', 'payment_term.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('paid', $log->details['status']['new']);
    }

    public function test_updating_a_material_deadline_is_logged(): void
    {
        $user = User::factory()->j4u()->create();
        $this->actingAs($user);

        $materialItem = Item::factory()->withMaterial()->create();

        $deal = Deal::factory()->create();
        $deal->items()->attach($materialItem->id);
        $deal->update(['status' => DealStatus::Finalized]);

        $deadline = $deal->materialDeadlines()->first();
        $deadline->update([
            'status' => MaterialStatus::Received,
            'received_at' => now(),
        ]);

        $log = ActivityLog::where('deal_id', $deal->id)
            ->where('action', 'material_deadline.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('received', $log->details['status']['new']);
        $this->assertNotNull($log->details['received_at']['new']);
    }
}
