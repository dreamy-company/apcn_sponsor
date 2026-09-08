<?php

namespace Database\Factories;

use App\Enums\GuaranteeLetterStatus;
use App\Models\GuaranteeLetter;
use App\Models\PaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuaranteeLetter>
 */
class GuaranteeLetterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_term_id' => PaymentTerm::factory(),
            'status' => GuaranteeLetterStatus::Uploaded,
            'doc_disk' => 'public',
            'doc_path' => 'guarantee-letters/1/1/surat.pdf',
            'doc_original_name' => 'surat.pdf',
            'doc_size' => 2048,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GuaranteeLetterStatus::Scheduled,
            'payment_due_date' => now()->addDays(14)->toDateString(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GuaranteeLetterStatus::Paid,
            'payment_due_date' => now()->subDay()->toDateString(),
            'proof_disk' => 'public',
            'proof_path' => 'guarantee-letters/1/1/proof/bukti.pdf',
            'proof_original_name' => 'bukti.pdf',
            'proof_size' => 1024,
        ]);
    }
}
