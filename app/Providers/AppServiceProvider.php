<?php

namespace App\Providers;

use App\Enums\RolesEnum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::authorizationView('passport.authorize');

        Passport::tokensCan([
            'read-profile' => 'Read the authenticated user basic profile.',
            'read-roles' => 'Read the authenticated user roles.',
            'read-permissions' => 'Read the authenticated user permissions.',
        ]);
        Passport::defaultScopes(['read-profile']);

        Gate::before(function ($user, $ability) {
            return $user->hasRole(RolesEnum::SUPERADMIN->value) ? true : null;
        });
    }
}
