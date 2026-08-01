<?php

namespace App\Providers;

use App\Events\Assignments\AssignmentCompleted;
use App\Events\Games\GameSessionCompleted;
use App\Events\Html\HtmlExerciseCompleted;
use App\Events\Html\WebpageProjectCompleted;
use App\Events\Typing\TypingSessionCompleted;
use App\Listeners\Rewards\ProcessCompletedAssignmentProgress;
use App\Listeners\Rewards\ProcessCompletedGameProgress;
use App\Listeners\Rewards\ProcessCompletedHtmlProgress;
use App\Listeners\Rewards\ProcessCompletedTypingProgress;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(AssignmentCompleted::class, ProcessCompletedAssignmentProgress::class);
        Event::listen(GameSessionCompleted::class, ProcessCompletedGameProgress::class);
        Event::listen(TypingSessionCompleted::class, ProcessCompletedTypingProgress::class);
        Event::listen(HtmlExerciseCompleted::class, ProcessCompletedHtmlProgress::class);
        Event::listen(WebpageProjectCompleted::class, ProcessCompletedHtmlProgress::class);
    }
}
