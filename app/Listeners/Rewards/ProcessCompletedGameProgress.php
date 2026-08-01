<?php

namespace App\Listeners\Rewards;

use App\Events\Games\GameSessionCompleted;
use App\Services\Rewards\ProgressEventProcessor;

class ProcessCompletedGameProgress
{
    public function __construct(private readonly ProgressEventProcessor $processor) {}

    public function handle(GameSessionCompleted $event): void
    {
        $this->processor->fromGameCompleted($event->session);
    }
}
