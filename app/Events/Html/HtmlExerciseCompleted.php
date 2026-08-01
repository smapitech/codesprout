<?php

namespace App\Events\Html;

use App\Models\HtmlAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HtmlExerciseCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public HtmlAttempt $attempt) {}
}
