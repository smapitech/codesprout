<?php

namespace App\Services\Games\Handlers;

use App\Enums\GameDifficulty;
use App\Models\GameSession;
use App\Models\GameVersion;
use App\Services\Games\Contracts\GameHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class AbstractGameHandler implements GameHandler
{
    public function validateConfiguration(array $configuration): void
    {
        $validator = Validator::make($configuration, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->rejectExecutableContent($configuration);
    }

    public function generateRounds(GameVersion $version, GameDifficulty $difficulty): array
    {
        $configuration = $version->configuration ?? [];
        $items = collect($configuration['items'] ?? $configuration['targets'] ?? $configuration['keys'] ?? $configuration['path'] ?? [])
            ->take($this->roundCount($version, $difficulty))
            ->values();

        return $items->map(fn ($item, int $index): array => [
            'round' => $index + 1,
            'prompt' => $this->promptFor($item),
            'expected' => $this->expectedFor($item),
            'safe' => $this->safeRoundData($item),
        ])->all();
    }

    public function sessionPayload(GameSession $session): array
    {
        $session->loadMissing(['gameVersion.definition', 'roundRecords']);
        $round = $session->roundRecords->firstWhere('round_number', $session->current_round) ?? $session->roundRecords->first();

        return [
            'session' => [
                'uuid' => $session->uuid,
                'status' => $session->status->value,
                'difficulty' => $session->difficulty->value,
                'current_round' => $session->current_round,
                'total_rounds' => $session->roundRecords->count(),
            ],
            'game' => [
                'name' => $session->gameVersion->definition->name,
                'category' => $session->gameVersion->definition->category->value,
                'game_type' => $session->gameVersion->definition->game_type->value,
                'instructions' => $session->gameVersion->instruction_content['written'] ?? $session->gameVersion->definition->instructions,
                'supported_input_methods' => $this->supportedInputMethods(),
            ],
            'round' => $round ? Arr::except($round->round_data, ['expected']) : null,
        ];
    }

    public function validateAction(GameSession $session, array $payload): array
    {
        $validator = Validator::make($payload, [
            'round_number' => ['required', 'integer', 'min:1'],
            'response' => ['required', 'array'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:300000'],
            'hint_used' => ['nullable', 'boolean'],
            'assistance_used' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function calculatePerformance(GameSession $session): array
    {
        $session->loadMissing(['roundRecords']);
        $completed = $session->roundRecords->filter(fn ($round): bool => $round->status === 'completed');
        $correct = $completed->where('is_correct', true)->count();
        $incorrect = $completed->where('is_correct', false)->count();
        $missed = (int) ($session->progress_data['missed_actions'] ?? 0);
        $total = max(1, $correct + $incorrect + $missed);
        $maximum = max(1, $session->roundRecords->count()) * 10;
        $accuracy = round(($correct / $total) * 100, 2);

        return [
            'correct_actions' => $correct,
            'incorrect_actions' => $incorrect,
            'missed_actions' => $missed,
            'total_actions' => $total,
            'accuracy' => $accuracy,
            'completion_time' => $session->started_at ? now()->diffInSeconds($session->started_at) : 0,
            'average_response_time' => (int) round($completed->avg('response_time_ms') ?? 0),
            'hints_used' => (int) ($session->progress_data['hints_used'] ?? 0),
            'assistance_used' => (int) ($session->progress_data['assistance_used'] ?? 0),
            'raw_metrics' => Arr::only($session->progress_data ?? [], ['longest_sequence', 'difficulty', 'completion_percentage']),
            'score' => round(($accuracy / 100) * $maximum, 2),
            'maximum_score' => $maximum,
            'completion_status' => $this->isComplete($session) ? 'completed' : 'partial',
            'calculated_at' => now(),
        ];
    }

    public function isComplete(GameSession $session): bool
    {
        $session->loadMissing('roundRecords');

        return $session->roundRecords->count() > 0
            && $session->roundRecords->every(fn ($round): bool => $round->status === 'completed');
    }

    public function feedbackFor(bool $correct): string
    {
        return $correct ? 'Great job! You found it!' : 'Almost there. Try once more.';
    }

    protected function roundCount(GameVersion $version, GameDifficulty $difficulty): int
    {
        $difficultyConfig = $version->difficulty_configuration[$difficulty->value] ?? [];

        return max(1, (int) ($difficultyConfig['rounds'] ?? $version->configuration['round_count'] ?? 5));
    }

    protected function promptFor(mixed $item): string
    {
        return is_array($item) ? (string) ($item['prompt'] ?? $item['name'] ?? $item['label'] ?? $item['key'] ?? 'Find the match') : (string) $item;
    }

    protected function expectedFor(mixed $item): mixed
    {
        return is_array($item) ? ($item['expected'] ?? $item['value'] ?? $item['key'] ?? $item['name'] ?? null) : $item;
    }

    protected function safeRoundData(mixed $item): array
    {
        return is_array($item) ? Arr::except($item, ['expected', 'is_correct', 'answer', 'answers']) : ['label' => (string) $item];
    }

    protected function normaliseKey(string $key): string
    {
        return match (strtolower($key)) {
            ' ', 'spacebar' => 'space',
            'esc' => 'escape',
            'arrowup' => 'arrow_up',
            'arrowdown' => 'arrow_down',
            'arrowleft' => 'arrow_left',
            'arrowright' => 'arrow_right',
            default => strtolower(trim($key)),
        };
    }

    protected function rejectExecutableContent(array $value): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);

        if (preg_match('/<script|on[a-z]+\s*=|javascript:|data:text\/html|eval\s*\(|new\s+function/i', $json) === 1) {
            throw ValidationException::withMessages([
                'configuration' => 'Game configuration must use safe declarative data only.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(): array;
}
