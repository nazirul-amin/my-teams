<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactCardAdminController;
use App\Http\Controllers\ContactCardPublicController;
use App\Http\Controllers\SharedController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public contact card
Route::get('contact-card/{slug}', [ContactCardPublicController::class, 'show'])->name('contact-card.public.show');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::post('companies/{company}/assign-users', [CompanyController::class, 'assignUsers'])->name('companies.assign-users');
    Route::resource('teams', TeamController::class);
    Route::get('shared/assignable-users', [SharedController::class, 'assignableUsers'])->name('shared.assignable-users');
    Route::get('shared/assigned-users', [SharedController::class, 'assignedUsers'])->name('shared.assigned-users');

    Route::post('teams/{team}/assign-users', [TeamController::class, 'assignUsers'])->name('teams.assign-users');
    Route::resource('members', UserController::class)->parameters(['members' => 'user']);
    Route::post('members/{user}/contact-card', [ContactCardAdminController::class, 'store'])->name('members.contact-card.store');
    // Helpers for contact card generation dialog
    Route::get('members/{user}/companies', [UserController::class, 'companies'])->name('members.companies');
    Route::get('companies/{company}/teams', [CompanyController::class, 'teams'])->name('companies.teams');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
