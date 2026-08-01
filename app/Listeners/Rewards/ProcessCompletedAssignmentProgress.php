<?php

namespace App\Listeners\Rewards;

use App\Events\Assignments\AssignmentCompleted;
use App\Services\Rewards\ProgressEventProcessor;

class ProcessCompletedAssignmentProgress
{
    public function __construct(private readonly ProgressEventProcessor $processor) {}

    public function handle(AssignmentCompleted $event): void
    {
        $this->processor->fromAssignmentCompleted($event->attempt);
    }
}
