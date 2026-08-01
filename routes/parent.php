<?php

use App\Http\Controllers\Parent\AssignmentController;
use App\Http\Controllers\Parent\ChildProfileController;
use App\Http\Controllers\Parent\DashboardController;
use App\Http\Controllers\Parent\GameController;
use App\Http\Controllers\Parent\HtmlController;
use App\Http\Controllers\Parent\ProgressController;
use App\Http\Controllers\Parent\TypingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.account', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('progress', [ProgressController::class, 'index'])->name('progress.index');
        Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::get('games', [GameController::class, 'index'])->name('games.index');
        Route::get('typing', [TypingController::class, 'index'])->name('typing.index');
        Route::get('html', [HtmlController::class, 'index'])->middleware(['feature:html_learning_engine', 'feature:html_parent_preview'])->name('html.index');
        Route::get('children/{child}', [ChildProfileController::class, 'show'])->name('children.show');
    });
