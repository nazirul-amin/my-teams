<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\User;

class UserPolicy
{
    /**
     * View any users.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin via Gate::before
        return $user->hasRole(RolesEnum::ADMIN->value);
    }

    /**
     * View a specific user.
     */
    public function view(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }
        // Admin can view users they created
        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            return $target->created_by === $user->id;
        }

        return false;
    }

    /**
     * Create users.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([RolesEnum::ADMIN->value]);
    }

    /**
     * Update users.
     */
    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }
        // Admin can update only users they created
        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            return $target->created_by === $user->id;
        }

        return false;
    }

    /**
     * Delete users.
     */
    public function delete(User $user, User $target): bool
    {
        // Only Super Admin (via Gate::before). Admin cannot delete.
        return false;
    }
}
