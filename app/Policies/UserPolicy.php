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
        // Admin can view list of users they created
        // Manager/User can view list filtered to users sharing both company and team
        return $user->hasAnyRole([
            RolesEnum::ADMIN->value,
            RolesEnum::MANAGER->value,
            RolesEnum::USER->value,
        ]);
    }

    /**
     * View a specific user.
     */
    public function view(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }
        // Admin can view users they created OR users who share a company
        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            if ($target->created_by === $user->id) {
                return true;
            }

            return $target->companies()->whereIn('companies.id', $user->companies()->pluck('companies.id'))->exists();
        }

        // Manager/User can view users who share BOTH company and team
        if ($user->hasAnyRole([RolesEnum::MANAGER->value, RolesEnum::USER->value])) {
            $sharesCompany = $target->companies()->whereIn('companies.id', $user->companies()->pluck('companies.id'))->exists();
            if (! $sharesCompany) {
                return false;
            }
            $sharesTeam = $target->teams()->whereIn('teams.id', $user->teams()->pluck('teams.id'))->exists();

            return $sharesTeam;
        }

        return false;
    }

    /**
     * Create users.
     */
    public function create(User $user): bool
    {
        // Super Admin via Gate::before
        return $user->hasRole(RolesEnum::ADMIN->value);
    }

    /**
     * Update users.
     */
    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }
        // Admin can update users they created OR users who share a company
        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            if ($target->created_by === $user->id) {
                return true;
            }

            return $target->companies()->whereIn('companies.id', $user->companies()->pluck('companies.id'))->exists();
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
