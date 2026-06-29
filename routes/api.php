<?php

use App\Enums\RolesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/oauth/userinfo', function (Request $request) {
    $user = $request->user();
    $token = $user?->token();

    if (! $token || ! $user->tokenCan('read-profile')) {
        abort(403, 'The token does not have the required [read-profile] scope.');
    }

    $user->loadMissing([
        'teams.company',
        'teams.users.roles',
        'companies',
        'slackAccount',
    ]);
    $teams = $user->teams
        ->sortBy('name')
        ->values()
        ->map(function ($team) {
            $manager = $team->users
                ->filter(fn ($candidate) => $candidate->roles->contains(
                    fn ($role) => $role->name === RolesEnum::MANAGER->value && $role->guard_name === 'web'
                ))
                ->sortBy('name')
                ->first();

            return [
                'id' => $team->getKey(),
                'name' => $team->name,
                'company' => [
                    'id' => $team->company?->getKey(),
                    'name' => $team->company?->name,
                ],
                'manager' => [
                    'id' => $manager?->getKey(),
                    'name' => $manager?->name,
                    'email' => $manager?->email,
                ],
            ];
        });

    $payload = [
        'id' => $user->getKey(),
        'name' => $user->name,
        'email' => $user->email,
        'email_verified_at' => $user->email_verified_at,
        'teams' => $teams,
        'slack' => [
            'user_id' => $user->slackAccount?->slack_user_id,
        ],
    ];

    if ($user->tokenCan('read-roles')) {
        $payload['roles'] = $user->getRoleNames()->values();
    }

    if ($user->tokenCan('read-permissions')) {
        $payload['permissions'] = $user->getAllPermissions()
            ->pluck('name')
            ->values();
    }

    return response()->json($payload);
})->middleware('auth:api');
