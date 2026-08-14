<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case ClinicOwner = 'clinic_owner';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Moderator => 'Moderator',
            self::ClinicOwner => 'Proprietar clinică',
            self::User => 'Utilizator',
        };
    }

    /**
     * Roles that can access the admin dashboard and moderate content.
     */
    public function canModerate(): bool
    {
        return in_array($this, [self::Admin, self::Moderator], true);
    }
}
