<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table): void {
            // The brand negotiated under the PT (company_name). One brand per deal.
            // Nullable so pre-existing sponsors stay valid; required going forward via the UI.
            $table->string('brand_name')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table): void {
            $table->dropColumn('brand_name');
        });
    }
};
