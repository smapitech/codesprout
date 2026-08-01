<?php

namespace App\Events\Games;

use App\Models\GameResult;
use App\Models\GameSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GamePerformanceRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public GameSession $session, public GameResult $result) {}
}
