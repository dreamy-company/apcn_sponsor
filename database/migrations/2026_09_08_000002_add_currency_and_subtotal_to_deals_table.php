<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            // Currency the deal is transacted in. Catalog carries a price per currency;
            // no FX conversion is performed (BR-10).
            $table->string('currency', 3)->default('IDR')->after('package_id');
            // Accumulated rate-card value of the chosen package + add-ons (BR-07).
            // Informational: final_price remains authoritative and manually entered (BR-02).
            $table->decimal('subtotal', 15, 2)->default(0)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'subtotal']);
        });
    }
};
