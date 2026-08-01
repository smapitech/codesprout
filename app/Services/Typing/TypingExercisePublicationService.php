<?php

namespace App\Services\Typing;

use App\Enums\ContentStatus;
use App\Enums\TypingBackspacePolicy;
use App\Enums\TypingCorrectionPolicy;
use App\Models\TypingExercise;
use App\Models\TypingExerciseVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TypingExercisePublicationService
{
    public function __construct(
        private readonly TypingExerciseRegistry $registry,
        private readonly TypingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, ?User $actor = null): TypingExercise
    {
        return DB::transaction(function () use ($data, $actor): TypingExercise {
            $exercise = TypingExercise::query()->create([
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'title' => $data['title'],
                'exercise_type' => $data['exercise_type'],
                'description' => $data['description'] ?? null,
                'child_instructions' => $data['child_instructions'],
                'teacher_instructions' => $data['teacher_instructions'] ?? null,
                'status' => ContentStatus::Draft,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $version = $this->createVersion($exercise, $data, 1);
            $exercise->forceFill(['current_version_id' => $version->id])->save();
            $this->audit->record('typing.exercise.created', $exercise, $actor, ['version' => 1]);

            return $exercise->fresh(['currentVersion.contentItems']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraftFrom(TypingExercise $exercise, array $data, ?User $actor = null): TypingExerciseVersion
    {
        return DB::transaction(function () use ($exercise, $data, $actor): TypingExerciseVersion {
            $exercise->loadMissing('currentVersion');
            $next = ((int) $exercise->versions()->max('version_number')) + 1;

            $version = $this->createVersion($exercise, $data, $next);
            $exercise->forceFill([
                'title' => $data['title'] ?? $exercise->title,
                'description' => $data['description'] ?? $exercise->description,
                'child_instructions' => $data['child_instructions'] ?? $exercise->child_instructions,
                'teacher_instructions' => $data['teacher_instructions'] ?? $exercise->teacher_instructions,
                'current_version_id' => $version->id,
                'updated_by' => $actor?->id,
                'status' => ContentStatus::Draft,
            ])->save();

            $this->audit->record('typing.exercise.version_created', $version, $actor, ['typing_exercise_id' => $exercise->id]);

            return $version->fresh(['contentItems']);
        });
    }

    public function publish(TypingExerciseVersion $version, ?User $actor = null): TypingExerciseVersion
    {
        return DB::transaction(function () use ($version, $actor): TypingExerciseVersion {
            $version->loadMissing(['exercise', 'contentItems']);
            $this->validateForPublication($version);

            $version->forceFill([
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'published_by' => $actor?->id,
            ])->save();

            $version->exercise->forceFill([
                'status' => ContentStatus::Published,
                'current_version_id' => $version->id,
                'updated_by' => $actor?->id,
            ])->save();

            $this->audit->record('typing.exercise.published', $version, $actor, [
                'typing_exercise_id' => $version->typing_exercise_id,
                'version_number' => $version->version_number,
            ]);

            return $version->fresh(['exercise', 'contentItems']);
        });
    }

    public function archive(TypingExercise $exercise, ?User $actor = null): TypingExercise
    {
        $exercise->forceFill(['status' => ContentStatus::Archived, 'archived_at' => now(), 'updated_by' => $actor?->id])->save();
        $this->audit->record('typing.exercise.archived', $exercise, $actor);

        return $exercise->fresh(['currentVersion']);
    }

    public function validateForPublication(TypingExerciseVersion $version): void
    {
        if ($version->status === ContentStatus::Published && $version->published_at) {
            return;
        }

        $version->loadMissing(['exercise', 'contentItems']);
        if ($version->exercise->title === '' || $version->exercise->child_instructions === '') {
            throw ValidationException::withMessages(['title' => 'Typing exercises need a title and child instructions.']);
        }

        if ((float) $version->accuracy_requirement < 0 || (float) $version->accuracy_requirement > 100) {
            throw ValidationException::withMessages(['accuracy_requirement' => 'Accuracy requirements must be between 0 and 100.']);
        }

        $handler = $this->registry->handlerFor($version);
        $handler->validateConfiguration($version->content_configuration ?? [], $version->contentItems->map(fn ($item): array => $item->toArray())->all());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createVersion(TypingExercise $exercise, array $data, int $versionNumber): TypingExerciseVersion
    {
        $configuration = $data['content_configuration'] ?? [];
        $items = $data['items'] ?? [];
        $caseSensitive = (bool) ($configuration['case_sensitive'] ?? (($data['case_sensitive'] ?? 'case_insensitive') === 'case_sensitive'));

        $this->registry->handlerFor((string) $exercise->exercise_type->value)->validateConfiguration($configuration, $items);

        $version = $exercise->versions()->create([
            'version_number' => $versionNumber,
            'typing_difficulty_profile_id' => $data['typing_difficulty_profile_id'] ?? null,
            'content_configuration' => $configuration,
            'case_sensitive' => $caseSensitive ? 'case_sensitive' : 'case_insensitive',
            'backspace_policy' => $data['backspace_policy'] ?? TypingBackspacePolicy::Allowed->value,
            'correction_policy' => $data['correction_policy'] ?? TypingCorrectionPolicy::Allowed->value,
            'input_method_policy' => $data['input_method_policy'] ?? 'any',
            'timer_configuration' => $data['timer_configuration'] ?? [],
            'completion_criteria' => $data['completion_criteria'] ?? ['minimum_items' => 1, 'minimum_accuracy' => 0, 'allow_pause' => true],
            'accuracy_requirement' => $data['accuracy_requirement'] ?? 0,
            'speed_requirement' => $data['speed_requirement'] ?? null,
            'assistance_configuration' => $data['assistance_configuration'] ?? [],
            'adaptive_configuration' => $data['adaptive_configuration'] ?? [],
            'status' => ContentStatus::Draft,
            'content_checksum' => $this->checksum($configuration, $items),
        ]);

        foreach (array_values($items) as $index => $item) {
            $expected = (string) $item['expected_text'];
            $version->contentItems()->create([
                'item_type' => $item['item_type'] ?? $exercise->exercise_type->value,
                'prompt_text' => $item['prompt_text'],
                'expected_text' => $expected,
                'display_text' => $item['display_text'] ?? $expected,
                'normalised_expected_text' => $caseSensitive ? $expected : mb_strtolower($expected),
                'audio_path' => $item['audio_path'] ?? null,
                'image_path' => $item['image_path'] ?? null,
                'target_keys' => $item['target_keys'] ?? [],
                'difficulty_order' => $item['difficulty_order'] ?? $index + 1,
                'display_order' => $item['display_order'] ?? $index + 1,
                'is_active' => $item['is_active'] ?? true,
                'metadata' => $item['metadata'] ?? [],
            ]);
        }

        if (! empty($data['skill_ids'])) {
            $version->skills()->syncWithPivotValues($data['skill_ids'], ['emphasis_level' => 'primary']);
        }

        return $version;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<int, array<string, mixed>>  $items
     */
    private function checksum(array $configuration, array $items): string
    {
        return hash('sha256', json_encode([$configuration, $items], JSON_THROW_ON_ERROR));
    }
}
