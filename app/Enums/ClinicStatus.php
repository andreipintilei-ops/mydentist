<?php

namespace App\Enums;

enum ClinicStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    /**
     * Human label in Romanian for admin/owner UIs.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Ciornă',
            self::Pending => 'În așteptare',
            self::Published => 'Publicat',
            self::Rejected => 'Respins',
        };
    }
}
