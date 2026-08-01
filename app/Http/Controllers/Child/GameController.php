<?php

namespace App\Http\Controllers\Child;

use App\Enums\GameDifficulty;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\GameActionRequest;
use App\Http\Requests\Games\GameStartRequest;
use App\Models\GameDefinition;
use App\Models\GameSession;
use App\Services\Games\GameSessionService;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function show(GameDefinition $game): Response
    {
        $game->loadMissing('currentVersion');
        abort_unless($game->currentVersion && $game->isPublished(), 404);

        return Inertia::render('child/games/show', [
            'game' => [
                'name' => $game->name,
                'description' => $game->description,
                'instructions' => $game->instructions,
                'category' => $game->category->label(),
                'game_type' => $game->game_type->value,
            ],
            'actions' => [
                'start' => route('child.games.start', $game, absolute: false),
            ],
        ]);
    }

    public function start(GameStartRequest $request, GameDefinition $game, GameSessionService $service)
    {
        $game->loadMissing('currentVersion.definition');
        abort_unless($game->currentVersion, 404);

        $session = $service->start(
            $game->currentVersion,
            $request->user(),
            $request->validated('difficulty') ?? GameDifficulty::Slow,
            $request->safe()->except('difficulty'),
        );

        return redirect()->route('child.games.play', $session);
    }

    public function play(GameSession $session, GameSessionService $service): Response
    {
        return Inertia::render('child/games/player', [
            'payload' => $service->payload($session, request()->user()),
            'actions' => [
                'action' => route('child.games.action', $session, absolute: false),
                'pause' => route('child.games.pause', $session, absolute: false),
                'resume' => route('child.games.resume', $session, absolute: false),
                'complete' => route('child.games.complete', $session, absolute: false),
                'leave' => route('child.dashboard', absolute: false),
            ],
        ]);
    }

    public function action(GameActionRequest $request, GameSession $session, GameSessionService $service)
    {
        return response()->json($service->recordAction($session, $request->user(), $request->validated()));
    }

    public function pause(GameSession $session, GameSessionService $service)
    {
        $service->pause($session, request()->user());

        return back();
    }

    public function resume(GameSession $session, GameSessionService $service)
    {
        $service->resume($session, request()->user());

        return back();
    }

    public function complete(GameSession $session, GameSessionService $service)
    {
        $service->complete($session, request()->user(), request()->header('Idempotency-Key'));

        return back();
    }
}
