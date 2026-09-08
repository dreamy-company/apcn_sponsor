<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_item', function (Blueprint $table): void {
            // How many units of the item the tier includes — e.g. Diamond bundles
            // 5 booths and 15 complimentary registrations. Existing rows are 1.
            $table->unsignedInteger('quantity')->default(1)->after('item_id');
        });

        Schema::table('deal_items', function (Blueprint $table): void {
            // Units actually taken on this deal. For a package item it defaults to
            // the tier's quantity; for an add-on the sponsor chooses.
            $table->unsignedInteger('quantity')->default(1)->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('package_item', function (Blueprint $table): void {
            $table->dropColumn('quantity');
        });

        Schema::table('deal_items', function (Blueprint $table): void {
            $table->dropColumn('quantity');
        });
    }
};
