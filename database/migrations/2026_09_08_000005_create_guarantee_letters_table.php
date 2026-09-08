<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantee_letters', function (Blueprint $table): void {
            $table->id();
            // At most one guarantee letter per deal.
            $table->foreignId('deal_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            // uploaded -> scheduled (payment_due_date set) -> paid (transfer proof uploaded)
            $table->string('status', 20)->default('uploaded');
            $table->date('payment_due_date')->nullable();

            // The guarantee letter document itself.
            $table->string('doc_disk')->nullable();
            $table->string('doc_path')->nullable();
            $table->string('doc_original_name')->nullable();
            $table->unsignedBigInteger('doc_size')->nullable();

            // Transfer proof for the payment the letter guarantees.
            $table->string('proof_disk')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->unsignedBigInteger('proof_size')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantee_letters');
    }
};
