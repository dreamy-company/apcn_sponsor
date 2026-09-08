<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inclusion is written once for the whole deal, not per line item: the team
     * describes what the sponsor gets as one paragraph, and a textarea per item
     * made the wizard unusable. `items.inclusion` stays as the catalog blurb.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            // What the sponsor gets on this deal, as one free-form block.
            $table->text('inclusion')->nullable()->after('subtotal');
        });

        // Fold any per-item text already captured into the deal-level field so
        // nothing written before this change is lost.
        foreach (DB::table('deals')->pluck('id') as $dealId) {
            $lines = DB::table('deal_items')
                ->join('items', 'items.id', '=', 'deal_items.item_id')
                ->where('deal_items.deal_id', $dealId)
                ->whereNotNull('deal_items.inclusion')
                ->where('deal_items.inclusion', '!=', '')
                ->orderBy('items.name')
                ->pluck('deal_items.inclusion', 'items.name');

            if ($lines->isEmpty()) {
                continue;
            }

            $merged = $lines
                ->map(fn (string $text, string $name): string => $name.': '.$text)
                ->implode("\n");

            DB::table('deals')->where('id', $dealId)->update(['inclusion' => $merged]);
        }

        Schema::table('deal_items', function (Blueprint $table): void {
            $table->dropColumn('inclusion');
        });
    }

    public function down(): void
    {
        Schema::table('deal_items', function (Blueprint $table): void {
            $table->text('inclusion')->nullable()->after('quantity');
        });

        Schema::table('deals', function (Blueprint $table): void {
            $table->dropColumn('inclusion');
        });
    }
};
