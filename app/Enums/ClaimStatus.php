<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'În așteptarea verificării',
            self::Verified => 'Verificat, în așteptarea aprobării',
            self::Approved => 'Aprobat',
            self::Rejected => 'Respins',
        };
    }
}
