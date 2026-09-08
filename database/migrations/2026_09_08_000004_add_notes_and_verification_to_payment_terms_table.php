<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            // Free-form context for the term (why this amount, what it covers).
            $table->text('notes')->nullable()->after('amount');
            // Set when J4U has checked the uploaded transfer proof against the amount.
            $table->timestamp('verified_at')->nullable()->after('proof_size');
            $table->foreignId('verified_by_id')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_by_id');
            $table->dropColumn(['notes', 'verified_at']);
        });
    }
};
