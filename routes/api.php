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

    $payload = [
        'id' => $user->getKey(),
        'name' => $user->name,
        'email' => $user->email,
        'email_verified_at' => $user->email_verified_at,
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
