<?php

namespace App\Services\Games;

use App\Enums\ContentStatus;
use App\Models\GameDefinition;
use App\Models\GameVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GamePublicationService
{
    public function __construct(
        private readonly GameRegistry $registry,
        private readonly GameAuditService $audit,
    ) {}

    public function validate(GameVersion $version): void
    {
        $version->loadMissing('definition');
        $messages = [];

        if (blank($version->definition->name)) {
            $messages['name'] = 'A game name is required.';
        }

        if (blank($version->definition->instructions) && blank($version->instruction_content['written'] ?? null)) {
            $messages['instructions'] = 'Child-facing instructions are required.';
        }

        if ($version->configuration === []) {
            $messages['configuration'] = 'A game configuration is required.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        $this->registry->handlerFor($version)->validateConfiguration($version->configuration ?? []);
    }

    public function publish(GameVersion $version, User $actor): GameVersion
    {
        return DB::transaction(function () use ($version, $actor): GameVersion {
            $version->loadMissing('definition');
            $this->validate($version);

            $version->forceFill([
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'published_by' => $actor->getKey(),
            ])->save();

            $version->definition->forceFill([
                'status' => ContentStatus::Published,
                'current_version_id' => $version->getKey(),
                'updated_by' => $actor->getKey(),
                'archived_at' => null,
            ])->save();

            $this->audit->record('game.published', $version, $actor, [
                'game_definition_id' => $version->game_definition_id,
                'version_number' => $version->version_number,
            ]);

            return $version->fresh(['definition']);
        });
    }

    public function archive(GameDefinition $game, User $actor): GameDefinition
    {
        $game->forceFill([
            'status' => ContentStatus::Archived,
            'archived_at' => now(),
            'updated_by' => $actor->getKey(),
        ])->save();

        $this->audit->record('game.archived', $game, $actor);

        return $game->fresh(['currentVersion']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDraftFrom(GameDefinition $game, array $payload, User $actor): GameVersion
    {
        return DB::transaction(function () use ($game, $payload, $actor): GameVersion {
            $game->loadMissing('versions');
            $latest = (int) $game->versions()->max('version_number');
            $version = $game->versions()->create([
                'version_number' => $latest + 1,
                'configuration' => $payload['configuration'] ?? $game->currentVersion?->configuration ?? [],
                'instruction_content' => $payload['instruction_content'] ?? $game->currentVersion?->instruction_content ?? [],
                'difficulty_configuration' => $payload['difficulty_configuration'] ?? $game->currentVersion?->difficulty_configuration ?? [],
                'supported_input_methods' => $payload['supported_input_methods'] ?? $game->currentVersion?->supported_input_methods ?? [],
                'status' => ContentStatus::Draft,
            ]);

            $game->forceFill([
                'name' => $payload['name'] ?? $game->name,
                'description' => $payload['description'] ?? $game->description,
                'instructions' => $payload['instructions'] ?? $game->instructions,
                'updated_by' => $actor->getKey(),
                'current_version_id' => $version->getKey(),
            ])->save();

            $this->audit->record('game.version.created', $version, $actor, [
                'game_definition_id' => $game->getKey(),
                'version_number' => $version->version_number,
            ]);

            return $version->fresh(['definition']);
        });
    }
}
