<?php

use App\Http\Controllers\Teacher\AssignmentAttemptController;
use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\ClassroomController;
use App\Http\Controllers\Teacher\CurriculumController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\GameController;
use App\Http\Controllers\Teacher\HtmlController;
use App\Http\Controllers\Teacher\ProgressController;
use App\Http\Controllers\Teacher\TypingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.account', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('progress', [ProgressController::class, 'index'])->name('progress.index');
        Route::get('games', [GameController::class, 'index'])->name('games.index');
        Route::get('games/results', [GameController::class, 'results'])->name('games.results');
        Route::get('games/{game}/preview', [GameController::class, 'preview'])->name('games.preview');
        Route::get('typing', [TypingController::class, 'index'])->name('typing.index');
        Route::get('typing/results', [TypingController::class, 'results'])->name('typing.results');
        Route::get('typing/{typing}/preview', [TypingController::class, 'preview'])->name('typing.preview');
        Route::middleware('feature:html_learning_engine')->group(function () {
            Route::get('html', [HtmlController::class, 'index'])->name('html.index');
            Route::get('html/{html}/preview', [HtmlController::class, 'preview'])->name('html.preview');
            Route::middleware('feature:html_teacher_review')->group(function () {
                Route::get('html/projects/{project}/review', [HtmlController::class, 'review'])->name('html.projects.review');
                Route::post('html/projects/{project}/review', [HtmlController::class, 'storeReview'])->middleware('throttle:30,1')->name('html.projects.review.store');
            });
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

                Route::get('attempts/{assignmentAttempt}', [AssignmentAttemptController::class, 'show'])->name('attempts.show');
                Route::patch('attempts/{assignmentAttempt}', [AssignmentAttemptController::class, 'update'])->name('attempts.update');
                Route::post('attempts/{assignmentAttempt}/return', [AssignmentAttemptController::class, 'returnForRetry'])->name('attempts.return');
                Route::post('attempts/{assignmentAttempt}/complete', [AssignmentAttemptController::class, 'complete'])->name('attempts.complete');
            });
        Route::get('curriculum', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::get('curriculum/{curriculumWorld}', [CurriculumController::class, 'show'])->name('curriculum.show');
        Route::get('classes/{learningClass}', [ClassroomController::class, 'show'])->name('classes.show');
    });
