<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Deal;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_inventory_panel_renders_sold_and_remaining(): void
    {
        $this->actingAs(User::factory()->j4u()->create());

        $item = Item::factory()->create(['name' => 'Gala Naming', 'quota' => 3]);
        $deal = Deal::factory()->finalized()->create(['package_id' => null]);
        $deal->items()->attach($item->id, ['is_addon' => true]);

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Inventory')
            ->assertSee('Gala Naming')
            ->assertSee('1/3')
            ->assertSee('2 left');
    }
}
