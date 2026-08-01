<?php

namespace App\Enums;

enum LearnerProgressStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Paused = 'paused';
}
