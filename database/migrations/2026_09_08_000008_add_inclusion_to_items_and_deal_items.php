<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            // What the sponsor actually gets for this item — the catalog default.
            $table->text('inclusion')->nullable()->after('type');
        });

        Schema::table('deal_items', function (Blueprint $table): void {
            // Per-deal override. NULL means "use the item's catalog inclusion".
            $table->text('inclusion')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('inclusion');
        });

        Schema::table('deal_items', function (Blueprint $table): void {
            $table->dropColumn('inclusion');
        });
    }
};
