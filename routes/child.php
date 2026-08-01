<?php

use App\Http\Controllers\Child\AssignmentAttemptController;
use App\Http\Controllers\Child\DashboardController;
use App\Http\Controllers\Child\GameController;
use App\Http\Controllers\Child\HtmlController;
use App\Http\Controllers\Child\JourneyController;
use App\Http\Controllers\Child\MissionController;
use App\Http\Controllers\Child\RewardController;
use App\Http\Controllers\Child\TypingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.account', 'role:child'])
    ->prefix('child')
    ->name('child.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('rewards', [RewardController::class, 'index'])->name('rewards.index');
        Route::prefix('typing')
            ->name('typing.')
            ->group(function () {
                Route::get('/', [TypingController::class, 'index'])->name('index');
                Route::post('{typing}/start', [TypingController::class, 'start'])->middleware('throttle:30,1')->name('start');
                Route::get('sessions/{session}', [TypingController::class, 'play'])->name('play');
                Route::post('sessions/{session}/batch', [TypingController::class, 'batch'])->middleware('throttle:120,1')->name('batch');
                Route::post('sessions/{session}/pause', [TypingController::class, 'pause'])->middleware('throttle:30,1')->name('pause');
                Route::post('sessions/{session}/resume', [TypingController::class, 'resume'])->middleware('throttle:30,1')->name('resume');
                Route::post('sessions/{session}/complete', [TypingController::class, 'complete'])->middleware('throttle:30,1')->name('complete');
            });
        Route::prefix('html')
            ->name('html.')
            ->middleware('feature:html_learning_engine')
            ->group(function () {
                Route::get('/', [HtmlController::class, 'index'])->name('index');
                Route::post('{html}/start', [HtmlController::class, 'start'])->middleware(['feature:html_code_editor', 'throttle:30,1'])->name('start');
                Route::middleware('feature:html_code_editor')->group(function () {
                    Route::get('attempts/{attempt}', [HtmlController::class, 'showAttempt'])->name('attempts.show');
                    Route::post('attempts/{attempt}/preview', [HtmlController::class, 'previewAttempt'])->middleware('throttle:30,1')->name('attempts.preview');
                    Route::post('attempts/{attempt}/complete', [HtmlController::class, 'completeAttempt'])->middleware('throttle:30,1')->name('attempts.complete');
                });
                Route::middleware('feature:html_project_assignments')->group(function () {
                    Route::post('templates/{template}/start', [HtmlController::class, 'startProject'])->middleware('throttle:20,1')->name('projects.start');
                    Route::get('projects/{project}', [HtmlController::class, 'showProject'])->name('projects.show');
                    Route::post('projects/{project}/preview', [HtmlController::class, 'previewProject'])->middleware('throttle:30,1')->name('projects.preview');
                    Route::post('projects/{project}/autosave', [HtmlController::class, 'autosave'])->middleware('throttle:60,1')->name('projects.autosave');
                    Route::post('projects/{project}/pause', [HtmlController::class, 'pause'])->middleware('throttle:30,1')->name('projects.pause');
                    Route::post('projects/{project}/resume', [HtmlController::class, 'resume'])->middleware('throttle:30,1')->name('projects.resume');
                    Route::post('projects/{project}/submit', [HtmlController::class, 'submit'])->middleware('throttle:20,1')->name('projects.submit');
                });
            });
        Route::prefix('games')
            ->name('games.')
            ->group(function () {
                Route::get('{game}', [GameController::class, 'show'])->name('show');
                Route::post('{game}/start', [GameController::class, 'start'])->name('start');
                Route::get('sessions/{session}', [GameController::class, 'play'])->name('play');
                Route::post('sessions/{session}/action', [GameController::class, 'action'])->middleware('throttle:120,1')->name('action');
                Route::post('sessions/{session}/pause', [GameController::class, 'pause'])->middleware('throttle:30,1')->name('pause');
                Route::post('sessions/{session}/resume', [GameController::class, 'resume'])->middleware('throttle:30,1')->name('resume');
                Route::post('sessions/{session}/complete', [GameController::class, 'complete'])->middleware('throttle:30,1')->name('complete');
            });
        Route::prefix('missions')
            ->name('missions.')
            ->group(function () {
                Route::get('/', [MissionController::class, 'index'])->name('index');
                Route::get('attempts/{assignmentAttempt}', [AssignmentAttemptController::class, 'show'])->name('attempts.show');
                Route::post('attempts/{assignmentAttempt}/responses/{assignmentItem}', [AssignmentAttemptController::class, 'storeResponse'])->name('attempts.responses.store');
                Route::post('attempts/{assignmentAttempt}/attachments/{assignmentItem}', [AssignmentAttemptController::class, 'attachment'])->name('attempts.attachments.store');
                Route::post('attempts/{assignmentAttempt}/html/{assignmentItem}', [AssignmentAttemptController::class, 'launchHtml'])->middleware(['feature:html_learning_engine', 'feature:html_code_editor'])->name('attempts.html.start');
                Route::post('attempts/{assignmentAttempt}/projects/{assignmentItem}', [AssignmentAttemptController::class, 'launchProject'])->middleware(['feature:html_learning_engine', 'feature:html_project_assignments'])->name('attempts.projects.start');
                Route::post('attempts/{assignmentAttempt}/submit', [AssignmentAttemptController::class, 'submit'])->name('attempts.submit');
                Route::get('{assignmentAllocation}', [MissionController::class, 'show'])->name('show');
                Route::post('{assignmentAllocation}/start', [MissionController::class, 'start'])->name('start');
            });
        Route::get('journey/{world?}', [JourneyController::class, 'show'])->name('journey');
    });
