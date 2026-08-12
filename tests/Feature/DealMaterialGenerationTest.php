<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\MaterialStatus;
use App\Models\Deal;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealMaterialGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalizing_a_deal_generates_material_deadlines_for_items_requiring_material(): void
    {
        $materialItem = Item::factory()->withMaterial()->create();
        $noMaterialItem = Item::factory()->withoutMaterial()->create();
        $addonMaterialItem = Item::factory()->withMaterial()->create();

        $deal = Deal::factory()->create();

        $deal->items()->attach([
            $materialItem->id => ['is_addon' => false, 'custom_price' => null],
            $noMaterialItem->id => ['is_addon' => false, 'custom_price' => null],
            $addonMaterialItem->id => ['is_addon' => true, 'custom_price' => 10_000_000],
        ]);

        $deal->update(['status' => DealStatus::Finalized]);

        $this->assertDatabaseHas('material_deadlines', [
            'deal_id' => $deal->id,
            'item_id' => $materialItem->id,
            'material_name' => $materialItem->name,
            'status' => MaterialStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('material_deadlines', [
            'deal_id' => $deal->id,
            'item_id' => $addonMaterialItem->id,
            'material_name' => $addonMaterialItem->name,
        ]);

        $this->assertDatabaseMissing('material_deadlines', [
            'deal_id' => $deal->id,
            'item_id' => $noMaterialItem->id,
        ]);

        $this->assertSame(2, $deal->materialDeadlines()->count());
    }

    public function test_draft_deals_do_not_generate_material_deadlines(): void
    {
        $materialItem = Item::factory()->withMaterial()->create();

        $deal = Deal::factory()->create();
        $deal->items()->attach($materialItem->id);

        $this->assertSame(0, $deal->materialDeadlines()->count());
    }

    public function test_refinalizing_a_deal_does_not_duplicate_material_deadlines(): void
    {
        $materialItem = Item::factory()->withMaterial()->create();

        $deal = Deal::factory()->create();
        $deal->items()->attach($materialItem->id);

        $deal->update(['status' => DealStatus::Finalized]);
        $deal->update(['status' => DealStatus::Draft]);
        $deal->update(['status' => DealStatus::Finalized]);

        $this->assertSame(1, $deal->materialDeadlines()->count());
    }

    public function test_material_deadline_status_can_be_marked_received(): void
    {
        $materialItem = Item::factory()->withMaterial()->create();

        $deal = Deal::factory()->create();
        $deal->items()->attach($materialItem->id);
        $deal->update(['status' => DealStatus::Finalized]);

        $deadline = $deal->materialDeadlines()->first();

        $deadline->update([
            'status' => MaterialStatus::Received,
            'received_at' => now(),
        ]);

        $this->assertDatabaseHas('material_deadlines', [
            'id' => $deadline->id,
            'status' => MaterialStatus::Received->value,
        ]);

        $this->assertNotNull($deadline->fresh()->received_at);
    }
}
