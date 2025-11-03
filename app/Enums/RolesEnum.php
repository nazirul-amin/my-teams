<?php

namespace App\Enums;

enum RolesEnum: string
{
    case SUPERADMIN = 'super-admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::USER => 'User',
        };
    }
}
