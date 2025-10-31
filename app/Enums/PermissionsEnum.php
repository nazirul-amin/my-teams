<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    // case NAMEINAPP = 'name-in-database';
    case CREATE_USER = 'create-user';
    case READ_USER = 'read-user';
    case UPDATE_USER = 'update-user';
    case DELETE_USER = 'delete-user';

    public function label(): string
    {
        return match ($this) {
            self::CREATE_USER => 'Create User',
            self::READ_USER => 'Read User',
            self::UPDATE_USER => 'Update User',
            self::DELETE_USER => 'Delete User',
        };
    }
}
