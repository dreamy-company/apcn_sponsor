<?php

namespace App\Enums;

enum GuaranteeLetterStatus: string
{
    case Uploaded = 'uploaded';
    case Scheduled = 'scheduled';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Letter received',
            self::Scheduled => 'Payment scheduled',
            self::Paid => 'Transfer proof received',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Uploaded => 'badge-soft badge-warning',
            self::Scheduled => 'badge-soft badge-info',
            self::Paid => 'badge-soft badge-success',
        };
    }
}
