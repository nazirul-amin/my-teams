<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RolesEnum::SUPERADMIN->value,
            RolesEnum::ADMIN->value,
            RolesEnum::MANAGER->value,
            RolesEnum::USER->value,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return true;
        }

        // Admins/managers/users can view if assigned to the team company (membership)
        if ($user->hasAnyRole([
            RolesEnum::ADMIN->value,
            RolesEnum::MANAGER->value,
            RolesEnum::USER->value,
        ])) {
            return $team->company
                ->users()
                ->where('users.id', $user->getKey())
                ->exists()
                || $team->company->created_by === $user->getKey();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            RolesEnum::SUPERADMIN->value,
            RolesEnum::ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            // Admins can update if they own the company or are a member of the company
            return $team->company->created_by === $user->getKey()
                || $team->company
                    ->users()
                    ->where('users.id', $user->getKey())
                    ->exists();
        }

        if ($user->hasRole(RolesEnum::MANAGER->value)) {
            // Managers can update teams they are assigned to
            return $team->users()
                ->where('users.id', $user->getKey())
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return true;
        }

        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            // Admins can delete if they own the company or are a member of the company
            return $team->company->created_by === $user->getKey()
                || $team->company
                    ->users()
                    ->where('users.id', $user->getKey())
                    ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $this->delete($user, $team);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $this->delete($user, $team);
    }
}
