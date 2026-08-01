<?php

namespace App\Events\Typing;

use App\Models\TypingSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingSessionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public TypingSession $session) {}
}
