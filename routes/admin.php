<?php

use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\HtmlController;
use App\Http\Controllers\Admin\RewardController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\TypingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.account', 'role:administrator'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('school')
            ->name('school.')
            ->group(function () {
                Route::get('/', [SchoolManagementController::class, 'index'])->name('index');
                Route::post('users', [SchoolManagementController::class, 'storeUser'])->middleware('throttle:20,1')->name('users.store');
                Route::patch('users/{user}/status', [SchoolManagementController::class, 'updateStatus'])->middleware('throttle:30,1')->name('users.status');
                Route::post('classes', [SchoolManagementController::class, 'storeClass'])->middleware('throttle:20,1')->name('classes.store');
                Route::post('connections', [SchoolManagementController::class, 'storeConnection'])->middleware('throttle:40,1')->name('connections.store');
            });

        Route::prefix('rewards')
            ->name('rewards.')
            ->group(function () {
                Route::get('/', [RewardController::class, 'index'])->name('index');
                Route::post('rules', [RewardController::class, 'storeRule'])->middleware('throttle:30,1')->name('rules.store');
                Route::put('rules/{rewardRule}', [RewardController::class, 'updateRule'])->middleware('throttle:30,1')->name('rules.update');
                Route::post('rules/{rewardRule}/publish', [RewardController::class, 'publishRule'])->middleware('throttle:20,1')->name('rules.publish');
                Route::post('rules/{rewardRule}/archive', [RewardController::class, 'archiveRule'])->middleware('throttle:20,1')->name('rules.archive');
                Route::post('badges', [RewardController::class, 'storeBadge'])->middleware('throttle:30,1')->name('badges.store');
                Route::post('badges/{badgeDefinition}/publish', [RewardController::class, 'publishBadge'])->middleware('throttle:20,1')->name('badges.publish');
            });

        Route::prefix('games')
            ->name('games.')
            ->group(function () {
                Route::get('/', [GameController::class, 'index'])->name('index');
                Route::get('create', [GameController::class, 'create'])->name('create');
                Route::post('/', [GameController::class, 'store'])->name('store');
                Route::get('{game}', [GameController::class, 'show'])->name('show');
                Route::get('{game}/edit', [GameController::class, 'edit'])->name('edit');
                Route::put('{game}', [GameController::class, 'update'])->name('update');
                Route::post('{game}/versions/{version}/publish', [GameController::class, 'publish'])->name('publish');
                Route::post('{game}/archive', [GameController::class, 'archive'])->name('archive');
            });

        Route::prefix('typing')
            ->name('typing.')
            ->group(function () {
                Route::get('/', [TypingController::class, 'index'])->name('index');
                Route::get('create', [TypingController::class, 'create'])->name('create');
                Route::post('/', [TypingController::class, 'store'])->middleware('throttle:30,1')->name('store');
                Route::get('{typing}', [TypingController::class, 'show'])->name('show');
                Route::get('{typing}/edit', [TypingController::class, 'edit'])->name('edit');
                Route::put('{typing}', [TypingController::class, 'update'])->middleware('throttle:30,1')->name('update');
                Route::post('{typing}/versions/{version}/publish', [TypingController::class, 'publish'])->middleware('throttle:20,1')->name('publish');
                Route::post('{typing}/archive', [TypingController::class, 'archive'])->middleware('throttle:20,1')->name('archive');
            });

        Route::prefix('html')
            ->name('html.')
            ->middleware('feature:html_learning_engine')
            ->group(function () {
                Route::get('/', [HtmlController::class, 'index'])->name('index');
                Route::get('create', [HtmlController::class, 'create'])->name('create');
                Route::post('/', [HtmlController::class, 'store'])->middleware('throttle:30,1')->name('store');
                Route::get('templates/create', [HtmlController::class, 'createTemplate'])->name('templates.create');
                Route::post('templates', [HtmlController::class, 'storeTemplate'])->middleware('throttle:30,1')->name('templates.store');
                Route::post('templates/{template}/versions/{version}/publish', [HtmlController::class, 'publishTemplate'])->middleware('throttle:20,1')->name('templates.publish');
                Route::get('{html}', [HtmlController::class, 'show'])->name('show');
                Route::get('{html}/edit', [HtmlController::class, 'edit'])->name('edit');
                Route::put('{html}', [HtmlController::class, 'update'])->middleware('throttle:30,1')->name('update');
                Route::post('{html}/versions/{version}/publish', [HtmlController::class, 'publish'])->middleware('throttle:20,1')->name('publish');
                Route::post('{html}/archive', [HtmlController::class, 'archive'])->middleware('throttle:20,1')->name('archive');
            });

        Route::prefix('assignments')
            ->name('assignments.')
            ->group(function () {
                Route::get('/', [AssignmentController::class, 'index'])->name('index');
                Route::get('create', [AssignmentController::class, 'create'])->name('create');
                Route::post('/', [AssignmentController::class, 'store'])->name('store');
                Route::get('{assignment}', [AssignmentController::class, 'show'])->name('show');
                Route::get('{assignment}/edit', [AssignmentController::class, 'edit'])->name('edit');
                Route::put('{assignment}', [AssignmentController::class, 'update'])->name('update');
                Route::post('{assignment}/publish', [AssignmentController::class, 'publish'])->name('publish');
                Route::post('{assignment}/allocate', [AssignmentController::class, 'allocate'])->name('allocate');
                Route::post('{assignment}/archive', [AssignmentController::class, 'archive'])->name('archive');
                Route::post('{assignment}/restore', [AssignmentController::class, 'restore'])->name('restore');
            });

        Route::prefix('curriculum')
            ->name('curriculum.')
            ->group(function () {
                Route::get('/', [CurriculumController::class, 'index'])->name('index');
                Route::get('create', [CurriculumController::class, 'create'])->name('create');
                Route::post('/', [CurriculumController::class, 'store'])->name('store');
                Route::post('import', [CurriculumController::class, 'import'])->name('import');
                Route::get('skills', [SkillController::class, 'index'])->name('skills');
                Route::post('{curriculum}/worlds/{world}/move/{direction}', [CurriculumController::class, 'moveWorld'])->name('worlds.move');
                Route::post('{curriculum}/worlds/{world}/duplicate', [CurriculumController::class, 'duplicateWorld'])->name('worlds.duplicate');
                Route::post('{curriculum}/worlds/{world}/units/{unit}/move/{direction}', [CurriculumController::class, 'moveUnit'])->name('units.move');
                Route::post('{curriculum}/worlds/{world}/units/{unit}/duplicate', [CurriculumController::class, 'duplicateUnit'])->name('units.duplicate');
                Route::post('{curriculum}/worlds/{world}/units/{unit}/lessons/{lesson}/move/{direction}', [CurriculumController::class, 'moveLesson'])->name('lessons.move');
                Route::post('{curriculum}/worlds/{world}/units/{unit}/lessons/{lesson}/duplicate', [CurriculumController::class, 'duplicateLesson'])->name('lessons.duplicate');
                Route::post('{curriculum}/worlds/{world}/units/{unit}/lessons/{lesson}/stages/{stage}/move/{direction}', [CurriculumController::class, 'moveStage'])->name('stages.move');
                Route::post('{curriculum}/worlds/{world}/units/{unit}/lessons/{lesson}/stages/{stage}/duplicate', [CurriculumController::class, 'duplicateStage'])->name('stages.duplicate');

                Route::get('{curriculum}', [CurriculumController::class, 'show'])->name('show');
                Route::get('{curriculum}/preview', [CurriculumController::class, 'preview'])->name('preview');
                Route::get('{curriculum}/edit', [CurriculumController::class, 'edit'])->name('edit');
                Route::put('{curriculum}', [CurriculumController::class, 'update'])->name('update');
                Route::post('{curriculum}/validate', [CurriculumController::class, 'validatePublication'])->name('validate');
                Route::post('{curriculum}/publish', [CurriculumController::class, 'publish'])->name('publish');
                Route::post('{curriculum}/archive', [CurriculumController::class, 'archive'])->name('archive');
                Route::post('{curriculum}/restore', [CurriculumController::class, 'restore'])->name('restore');
                Route::get('{curriculum}/export', [CurriculumController::class, 'export'])->name('export');
            });
    });
