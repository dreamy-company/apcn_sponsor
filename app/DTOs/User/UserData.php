<?php

namespace App\DTOs\User;

use App\Enums\UserRole;

/**
 * Plain data carrier for user creation/update. No Model dependencies.
 */
final readonly class UserData
{
    /**
     * @param  UserRole  $role  Doctor (initiator) or J4U (organizing committee).
     * @param  string|null  $password  Plaintext password; null/empty keeps the existing password on update.
     */
    public function __construct(
        public string $name,
        public string $email,
        public UserRole $role,
        public ?string $password,
    ) {}
}
