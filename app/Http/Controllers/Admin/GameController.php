<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\GameCategory;
use App\Enums\GameDifficulty;
use App\Enums\GameType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\GameDefinitionRequest;
use App\Http\Requests\Games\GameVersionRequest;
use App\Models\GameDefinition;
use App\Models\GameVersion;
use App\Services\Games\GamePublicationService;
use App\Services\Games\GameRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(): Response
    {
        $games = GameDefinition::query()
            ->with(['currentVersion'])
            ->withCount(['versions'])
            ->orderBy('name')
            ->get()
            ->map(fn (GameDefinition $game): array => $this->gameRow($game));

        return Inertia::render('games/index', [
            'role' => 'administrator',
            'games' => $games,
            'summary' => [
                'published' => $games->where('status', ContentStatus::Published->value)->count(),
                'draft' => $games->where('status', ContentStatus::Draft->value)->count(),
                'archived' => $games->where('status', ContentStatus::Archived->value)->count(),
            ],
            'createHref' => route('admin.games.create', absolute: false),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('games/form', [
            'mode' => 'create',
            'game' => null,
            'action' => route('admin.games.store', absolute: false),
            'categoryOptions' => $this->categoryOptions(),
            'typeOptions' => $this->typeOptions(),
            'difficultyOptions' => $this->difficultyOptions(),
        ]);
    }

    public function store(GameDefinitionRequest $request, GameRegistry $registry): RedirectResponse
    {
        $validated = $request->validated();
        $registry->handlerFor($validated['game_type'])->validateConfiguration($validated['configuration']);

        $game = DB::transaction(function () use ($validated, $request): GameDefinition {
            $game = GameDefinition::query()->create([
                'slug' => $validated['slug'] ?? Str::slug($validated['name']),
                'name' => $validated['name'],
                'category' => $validated['category'],
                'game_type' => $validated['game_type'],
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'],
                'status' => $validated['status'] ?? ContentStatus::Draft->value,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $version = $game->versions()->create([
                'version_number' => 1,
                'configuration' => $validated['configuration'],
                'instruction_content' => $validated['instruction_content'] ?? ['written' => $validated['instructions']],
                'difficulty_configuration' => $validated['difficulty_configuration'] ?? [],
                'supported_input_methods' => $validated['supported_input_methods'] ?? [],
                'status' => ContentStatus::Draft,
            ]);

            $game->forceFill(['current_version_id' => $version->id])->save();

            return $game->fresh(['currentVersion']);
        });

        return to_route('admin.games.show', $game)->with('status', 'Game created safely.');
    }

    public function show(GameDefinition $game): Response
    {
        $game->loadMissing(['currentVersion', 'versions']);

        return Inertia::render('games/show', [
            'role' => 'administrator',
            'game' => $this->gameDetails($game),
            'actions' => [
                'edit' => route('admin.games.edit', $game, absolute: false),
                'publish' => $game->currentVersion ? route('admin.games.publish', [$game, $game->currentVersion], absolute: false) : null,
                'archive' => route('admin.games.archive', $game, absolute: false),
            ],
        ]);
    }

    public function edit(GameDefinition $game): Response
    {
        $game->loadMissing('currentVersion');

        return Inertia::render('games/form', [
            'mode' => 'edit',
            'game' => $this->gameDetails($game),
            'action' => route('admin.games.update', $game, absolute: false),
            'categoryOptions' => $this->categoryOptions(),
            'typeOptions' => $this->typeOptions(),
            'difficultyOptions' => $this->difficultyOptions(),
        ]);
    }

    public function update(GameVersionRequest $request, GameDefinition $game, GamePublicationService $publicationService): RedirectResponse
    {
        $publicationService->createDraftFrom($game, $request->validated(), $request->user());

        return to_route('admin.games.show', $game)->with('status', 'A new draft game version was created.');
    }

    public function publish(GameDefinition $game, GameVersion $version, GamePublicationService $publicationService): RedirectResponse
    {
        abort_unless($version->game_definition_id === $game->id, 404);
        $publicationService->publish($version, request()->user());

        return to_route('admin.games.show', $game)->with('status', 'Game published.');
    }

    public function archive(GameDefinition $game, GamePublicationService $publicationService): RedirectResponse
    {
        $publicationService->archive($game, request()->user());

        return to_route('admin.games.show', $game)->with('status', 'Game archived.');
    }

    private function gameRow(GameDefinition $game): array
    {
        return [
            'id' => $game->id,
            'slug' => $game->slug,
            'name' => $game->name,
            'category' => $game->category->label(),
            'game_type' => $game->game_type->label(),
            'status' => $game->status->value,
            'version_count' => $game->versions_count ?? $game->versions()->count(),
            'current_version' => $game->currentVersion?->version_number,
            'href' => route('admin.games.show', $game, absolute: false),
        ];
    }

    private function gameDetails(GameDefinition $game): array
    {
        return array_merge($this->gameRow($game), [
            'description' => $game->description,
            'instructions' => $game->instructions,
            'currentVersion' => $game->currentVersion ? [
                'id' => $game->currentVersion->id,
                'version_number' => $game->currentVersion->version_number,
                'status' => $game->currentVersion->status->value,
                'configuration' => $game->currentVersion->configuration,
                'instruction_content' => $game->currentVersion->instruction_content,
                'difficulty_configuration' => $game->currentVersion->difficulty_configuration,
                'supported_input_methods' => $game->currentVersion->supported_input_methods,
            ] : null,
        ]);
    }

    private function categoryOptions(): array
    {
        return array_map(fn (GameCategory $category): array => ['value' => $category->value, 'label' => $category->label()], GameCategory::cases());
    }

    private function typeOptions(): array
    {
        return array_map(fn (GameType $type): array => ['value' => $type->value, 'label' => $type->label()], GameType::cases());
    }

    private function difficultyOptions(): array
    {
        return array_map(fn (GameDifficulty $difficulty): array => ['value' => $difficulty->value, 'label' => $difficulty->label()], GameDifficulty::cases());
    }
}
