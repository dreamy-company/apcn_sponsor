<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            // Transfer proof (bukti transfer) attached to a payment term. NULL = none.
            $table->string('proof_disk')->nullable()->after('status');
            $table->string('proof_path')->nullable()->after('proof_disk');
            $table->string('proof_original_name')->nullable()->after('proof_path');
            $table->unsignedBigInteger('proof_size')->nullable()->after('proof_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            $table->dropColumn(['proof_disk', 'proof_path', 'proof_original_name', 'proof_size']);
        });
    }
};
