<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            // Rate-card price of the item when sold separately as an add-on. NULL = quote on request.
            $table->decimal('default_price', 15, 2)->nullable()->after('quota');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('default_price');
        });
    }
};
