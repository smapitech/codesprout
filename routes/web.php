<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', WelcomeController::class)->name('home');

Route::get('privacy', function () {
    return Inertia::render('privacy');
})->name('privacy');

Route::get('terms', function () {
    return Inertia::render('terms');
})->name('terms');

Route::middleware(['auth', 'active.account'])
    ->get('dashboard', DashboardRedirectController::class)
    ->name('dashboard');

require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';
require __DIR__.'/parent.php';
require __DIR__.'/child.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
