<?php

namespace App\Enums;

enum UserRole: string
{
    case J4U = 'j4u';
    case Doctor = 'doctor';

    public function label(): string
    {
        return match ($this) {
            self::J4U => 'Tim J4U',
            self::Doctor => 'Dokter',
        };
    }
}
