<?php

namespace App\Enums;

use App\Models\User;

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

    /**
     * Roles the given user may assign to another user.
     *
     * @return list<self>
     */
    public static function assignableBy(User $user): array
    {
        if ($user->hasRole(self::SUPERADMIN->value)) {
            return self::cases();
        }

        return [self::ADMIN, self::MANAGER, self::USER];
    }

    /**
     * Options suitable for select controls.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function optionsAssignableBy(User $user): array
    {
        return array_map(
            fn (self $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            self::assignableBy($user),
        );
    }

    /**
     * @return list<string>
     */
    public static function valuesAssignableBy(User $user): array
    {
        return array_map(
            fn (self $role): string => $role->value,
            self::assignableBy($user),
        );
    }
}
