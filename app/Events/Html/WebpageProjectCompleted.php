<?php

namespace App\Events\Html;

use App\Models\LearnerWebpageProject;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebpageProjectCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public LearnerWebpageProject $project) {}
}
