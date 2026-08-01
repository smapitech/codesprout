<?php

namespace App\Events\Games;

use App\Models\GameSession;
use App\Models\GameSessionRound;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameRoundCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public GameSession $session, public GameSessionRound $round) {}
}
