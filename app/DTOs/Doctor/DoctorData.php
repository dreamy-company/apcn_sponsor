<?php

namespace App\DTOs\Doctor;

/**
 * Plain data carrier for doctor (initiator) create/update.
 * Doctors are non-login users; they have a name and a contact only.
 */
final readonly class DoctorData
{
    public function __construct(
        public string $name,
        public ?string $phone,
    ) {}
}
