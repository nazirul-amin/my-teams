<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Allow listing; controller will scope results by membership
        return $user->hasAnyRole([
            RolesEnum::ADMIN->value,
            RolesEnum::MANAGER->value,
            RolesEnum::USER->value,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        // Any member can read
        return $company->users()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin can create (Super Admin via Gate::before)
        return $user->hasAnyRole([
            RolesEnum::ADMIN->value,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        // Admin can update companies where they are a member
        return $user->hasAnyRole([
            RolesEnum::ADMIN->value,
        ])
            && $company->users()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Admin can delete companies where they are a member
        return $user->hasRole(RolesEnum::ADMIN->value)
            && $company->users()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        return $this->delete($user, $company);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $this->delete($user, $company);
    }
}
