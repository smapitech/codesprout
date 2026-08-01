<?php

use App\Http\Controllers\Admin\CurriculumController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active.account', 'role:administrator'])
    ->prefix('api')
    ->name('api.')
    ->group(function () {
        Route::get('curricula/{curriculum}', [CurriculumController::class, 'export'])->name('curricula.export');
    });
