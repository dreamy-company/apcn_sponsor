<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A guarantee letter is not a deal-level instrument: it is how one payment
     * term gets settled. Re-point it at the term, and drop amount/currency —
     * the letter guarantees the term's own amount, so a second figure could
     * only ever drift out of sync.
     */
    public function up(): void
    {
        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->foreignId('payment_term_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();
        });

        // Backfill: attach each existing letter to its deal's earliest term.
        foreach (DB::table('guarantee_letters')->get() as $letter) {
            $termId = DB::table('payment_terms')
                ->where('deal_id', $letter->deal_id)
                ->orderBy('due_date')
                ->orderBy('id')
                ->value('id');

            if ($termId === null) {
                // A deal with no terms has nothing for the letter to guarantee.
                DB::table('guarantee_letters')->where('id', $letter->id)->delete();

                continue;
            }

            DB::table('guarantee_letters')
                ->where('id', $letter->id)
                ->update(['payment_term_id' => $termId]);
        }

        // Two letters could have collapsed onto the same term; keep the newest.
        $seen = [];

        foreach (DB::table('guarantee_letters')->orderByDesc('id')->get() as $letter) {
            if (in_array($letter->payment_term_id, $seen, true)) {
                DB::table('guarantee_letters')->where('id', $letter->id)->delete();

                continue;
            }

            $seen[] = $letter->payment_term_id;
        }

        // Order matters and differs per driver: MySQL will not drop a unique
        // index while a foreign key still relies on it, and SQLite will not drop
        // a column while an index still references it. Dropping FK -> index ->
        // column satisfies both.
        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->dropForeign(['deal_id']);
        });

        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->dropUnique(['deal_id']);
        });

        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->dropColumn(['deal_id', 'amount', 'currency']);
        });

        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_term_id')->nullable(false)->change();
            $table->unique('payment_term_id');
        });
    }

    public function down(): void
    {
        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->dropUnique(['payment_term_id']);
            $table->foreignId('deal_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
        });

        foreach (DB::table('guarantee_letters')->get() as $letter) {
            $term = DB::table('payment_terms')->where('id', $letter->payment_term_id)->first();

            if ($term === null) {
                continue;
            }

            DB::table('guarantee_letters')->where('id', $letter->id)->update([
                'deal_id' => $term->deal_id,
                'amount' => $term->amount,
            ]);
        }

        Schema::table('guarantee_letters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_term_id');
        });
    }
};
