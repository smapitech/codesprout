<?php

namespace App\Events\Assignments;

use App\Models\AssignmentAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssignmentReturnedForRetry
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly AssignmentAttempt $attempt) {}
}
