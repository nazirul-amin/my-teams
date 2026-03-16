<?php

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
        'companies',
        'slackAccount',
    ]);

    $team = $user->teams->sortBy('name')->first();
    $company = $team?->company ?? $user->companies->sortBy('name')->first();

    $payload = [
        'id' => $user->getKey(),
        'name' => $user->name,
        'email' => $user->email,
        'email_verified_at' => $user->email_verified_at,
        'team' => [
            'id' => $team?->getKey(),
            'name' => $team?->name,
        ],
        'company' => [
            'id' => $company?->getKey(),
            'name' => $company?->name,
        ],
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
