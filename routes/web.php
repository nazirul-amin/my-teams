<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactCardPublicController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public contact card
Route::get('c/{slug}', [ContactCardPublicController::class, 'show'])->name('contact-card.public.show');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::get('companies/{company}/users', [CompanyController::class, 'users'])->name('companies.users');
    Route::resource('teams', TeamController::class);
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
