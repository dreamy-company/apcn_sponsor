<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing default_price values are IDR — rename rather than recreate so they survive.
        Schema::table('items', function (Blueprint $table): void {
            $table->renameColumn('default_price', 'default_price_idr');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->renameColumn('default_price', 'default_price_idr');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->decimal('default_price_usd', 15, 2)->nullable()->after('default_price_idr');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->decimal('default_price_usd', 15, 2)->nullable()->after('default_price_idr');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('default_price_usd');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn('default_price_usd');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->renameColumn('default_price_idr', 'default_price');
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->renameColumn('default_price_idr', 'default_price');
        });
    }
};
