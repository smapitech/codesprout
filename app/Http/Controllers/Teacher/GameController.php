<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ContentStatus;
use App\Enums\GameDifficulty;
use App\Http\Controllers\Controller;
use App\Models\GameDefinition;
use App\Services\Games\GameRegistry;
use App\Services\Games\GameReportService;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(): Response
    {
        $games = GameDefinition::query()
            ->with('currentVersion')
            ->where('status', ContentStatus::Published)
            ->orderBy('name')
            ->get()
            ->map(fn (GameDefinition $game): array => [
                'slug' => $game->slug,
                'name' => $game->name,
                'category' => $game->category->label(),
                'game_type' => $game->game_type->label(),
                'status' => $game->status->value,
                'supported_input_methods' => $game->currentVersion?->supported_input_methods ?? [],
                'preview_href' => route('teacher.games.preview', $game, absolute: false),
            ]);

        return Inertia::render('games/index', [
            'role' => 'teacher',
            'games' => $games,
        ]);
    }

    public function preview(GameDefinition $game, GameRegistry $registry): Response
    {
        $game->loadMissing('currentVersion.definition');
        abort_unless($game->isPublished() && $game->currentVersion, 404);

        $handler = $registry->handlerFor($game->currentVersion);

        return Inertia::render('games/preview', [
            'preview' => true,
            'game' => [
                'name' => $game->name,
                'category' => $game->category->label(),
                'game_type' => $game->game_type->value,
                'instructions' => $game->instructions,
                'supported_input_methods' => $handler->supportedInputMethods(),
                'round' => $handler->generateRounds($game->currentVersion, GameDifficulty::Slow)[0] ?? null,
            ],
        ]);
    }

    public function results(GameReportService $reportService): Response
    {
        return Inertia::render('games/results', [
            'summary' => $reportService->teacherSummary(request()->user()),
        ]);
    }
}
