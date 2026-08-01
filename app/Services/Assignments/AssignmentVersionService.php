<?php

namespace App\Services\Assignments;

use App\Enums\AssignmentType;
use App\Enums\ContentStatus;
use App\Models\Assignment;
use App\Models\AssignmentItem;
use App\Models\AssignmentItemOption;
use App\Models\AssignmentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentVersionService
{
    public function __construct(
        private readonly AssignmentQuestionHandlerRegistry $registry,
    ) {}

    /**
     * Create a new assignment with an initial draft version.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createDraft(array $payload, User $actor): AssignmentVersion
    {
        return DB::transaction(function () use ($payload, $actor): AssignmentVersion {
            $assignment = new Assignment;
            $assignment->fill([
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'assignment_type' => $payload['assignment_type'] ?? AssignmentType::Mission->value,
                'status' => ContentStatus::Draft,
            ]);
            $assignment->save();

            return $this->saveDraft($assignment, $payload, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveDraft(Assignment $assignment, array $payload, User $actor): AssignmentVersion
    {
        return DB::transaction(function () use ($assignment, $payload, $actor): AssignmentVersion {
            $assignment->loadMissing(['currentVersion.items.options', 'versions']);

            $version = $assignment->currentVersion;

            if ($version?->isPublished()) {
                $version = $this->duplicateForEditing($version, $actor);
            }

            if (! $version) {
                $version = new AssignmentVersion([
                    'assignment_id' => $assignment->id,
                    'version_number' => max(1, (int) $assignment->versions()->max('version_number') + 1),
                    'title' => '',
                    'estimated_minutes' => 10,
                    'difficulty_level' => $payload['difficulty_level'] ?? 'introductory',
                    'default_attempt_limit' => 1,
                    'feedback_mode' => $payload['feedback_mode'] ?? 'after_submission',
                    'scoring_method' => $payload['scoring_method'] ?? 'latest_attempt',
                    'status' => ContentStatus::Draft,
                    'total_points' => 0,
                ]);
                $version->save();
            }

            $this->fillVersion($version, $payload);
            $this->syncVersionContent($version, $payload);

            $assignment->assignment_type = $payload['assignment_type'] ?? ($assignment->assignment_type instanceof AssignmentType ? $assignment->assignment_type->value : (string) $assignment->assignment_type);
            $assignment->status = $assignment->status instanceof ContentStatus ? $assignment->status : ContentStatus::Draft;
            $assignment->current_version_id = $version->getKey();
            $assignment->save();

            return $version->fresh(['items.options', 'curriculumLinks', 'skills', 'assignment']);
        });
    }

    public function duplicateForEditing(AssignmentVersion $source, ?User $actor = null): AssignmentVersion
    {
        return DB::transaction(function () use ($source, $actor): AssignmentVersion {
            $source->loadMissing(['assignment.versions', 'items.options', 'curriculumLinks', 'skills']);
            $assignment = $source->assignment;
            $versionNumber = max(1, (int) $assignment->versions()->max('version_number') + 1);

            $copy = $source->replicate([
                'status',
                'published_at',
                'published_by',
                'total_points',
            ]);

            $copy->assignment_id = $assignment->id;
            $copy->version_number = $versionNumber;
            $copy->status = ContentStatus::Draft;
            $copy->published_at = null;
            $copy->published_by = null;
            $copy->save();

            foreach ($source->items as $item) {
                $clonedItem = $item->replicate([
                    'assignment_version_id',
                    'created_at',
                    'updated_at',
                ]);
                $clonedItem->assignment_version_id = $copy->getKey();
                $clonedItem->save();

                foreach ($item->options as $option) {
                    $clonedOption = $option->replicate([
                        'assignment_item_id',
                        'created_at',
                        'updated_at',
                    ]);
                    $clonedOption->assignment_item_id = $clonedItem->getKey();
                    $clonedOption->save();
                }
            }

            foreach ($source->curriculumLinks as $link) {
                $clonedLink = $link->replicate(['assignment_version_id', 'created_at', 'updated_at']);
                $clonedLink->assignment_version_id = $copy->getKey();
                $clonedLink->save();
            }

            $copy->skills()->sync(
                $source->skills->mapWithKeys(static fn ($skill): array => [
                    $skill->getKey() => ['emphasis_level' => (int) ($skill->pivot->emphasis_level ?? 1)],
                ])->all(),
            );

            $assignment->current_version_id = $copy->getKey();
            $assignment->save();

            if ($actor) {
                app(AssignmentAuditService::class)->record('assignment.version.duplicated', $copy, $actor, [
                    'assignment_id' => $assignment->getKey(),
                    'source_version_id' => $source->getKey(),
                    'version_number' => $versionNumber,
                ]);
            }

            return $copy->fresh(['items.options', 'curriculumLinks', 'skills', 'assignment']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fillVersion(AssignmentVersion $version, array $payload): AssignmentVersion
    {
        $version->fill([
            'title' => trim((string) ($payload['title'] ?? $version->title)),
            'short_description' => blank($payload['short_description'] ?? null) ? null : trim((string) $payload['short_description']),
            'child_instructions' => blank($payload['child_instructions'] ?? null) ? null : trim((string) $payload['child_instructions']),
            'teacher_instructions' => blank($payload['teacher_instructions'] ?? null) ? null : trim((string) $payload['teacher_instructions']),
            'audio_instruction_path' => blank($payload['audio_instruction_path'] ?? null) ? null : (string) $payload['audio_instruction_path'],
            'estimated_minutes' => (int) ($payload['estimated_minutes'] ?? $version->estimated_minutes ?? 10),
            'difficulty_level' => $payload['difficulty_level'] ?? $version->difficulty_level ?? 'introductory',
            'default_attempt_limit' => (int) ($payload['default_attempt_limit'] ?? $version->default_attempt_limit ?? 1),
            'feedback_mode' => $payload['feedback_mode'] ?? $version->feedback_mode ?? 'after_submission',
            'scoring_method' => $payload['scoring_method'] ?? $version->scoring_method ?? 'latest_attempt',
            'settings' => $payload['settings'] ?? $version->settings ?? [],
        ]);

        $version->save();

        return $version;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function syncVersionContent(AssignmentVersion $version, array $payload): AssignmentVersion
    {
        $items = collect($payload['items'] ?? []);
        $curriculumLinks = collect($payload['curriculum_links'] ?? []);
        $skillIds = collect($payload['skill_ids'] ?? [])->map(static fn ($value): int => (int) $value)->filter()->values()->all();

        $version->items()->delete();
        $version->curriculumLinks()->delete();

        $displayOrder = 1;
        $totalPoints = 0;

        foreach ($items as $itemPayload) {
            $item = new AssignmentItem;
            $item->fill([
                'html_exercise_version_id' => $itemPayload['html_exercise_version_id'] ?? null,
                'project_template_version_id' => $itemPayload['project_template_version_id'] ?? null,
                'title' => trim((string) ($itemPayload['title'] ?? '')),
                'prompt_text' => blank($itemPayload['prompt_text'] ?? null) ? null : trim((string) $itemPayload['prompt_text']),
                'audio_prompt_path' => blank($itemPayload['audio_prompt_path'] ?? null) ? null : (string) $itemPayload['audio_prompt_path'],
                'image_path' => blank($itemPayload['image_path'] ?? null) ? null : (string) $itemPayload['image_path'],
                'question_type' => $itemPayload['question_type'] ?? '',
                'interaction_type' => $itemPayload['interaction_type'] ?? '',
                'points' => max(0, (int) ($itemPayload['points'] ?? 0)),
                'is_required' => (bool) ($itemPayload['is_required'] ?? true),
                'hint_text' => blank($itemPayload['hint_text'] ?? null) ? null : trim((string) $itemPayload['hint_text']),
                'hint_audio_path' => blank($itemPayload['hint_audio_path'] ?? null) ? null : (string) $itemPayload['hint_audio_path'],
                'explanation_text' => blank($itemPayload['explanation_text'] ?? null) ? null : trim((string) $itemPayload['explanation_text']),
                'display_order' => (int) ($itemPayload['display_order'] ?? $displayOrder),
                'configuration' => $itemPayload['configuration'] ?? [],
                'grading_configuration' => $itemPayload['grading_configuration'] ?? [],
            ]);
            $item->assignment_version_id = $version->getKey();
            $item->save();

            foreach (collect($itemPayload['options'] ?? [])->values() as $index => $optionPayload) {
                $option = new AssignmentItemOption;
                $option->fill([
                    'option_text' => blank($optionPayload['option_text'] ?? null) ? null : trim((string) $optionPayload['option_text']),
                    'image_path' => blank($optionPayload['image_path'] ?? null) ? null : (string) $optionPayload['image_path'],
                    'option_value' => blank($optionPayload['option_value'] ?? null) ? null : trim((string) $optionPayload['option_value']),
                    'is_correct' => (bool) ($optionPayload['is_correct'] ?? false),
                    'matching_key' => blank($optionPayload['matching_key'] ?? null) ? null : trim((string) $optionPayload['matching_key']),
                    'display_order' => (int) ($optionPayload['display_order'] ?? ($index + 1)),
                ]);
                $option->assignment_item_id = $item->getKey();
                $option->save();
            }

            $totalPoints += $item->points;
            $displayOrder++;
        }

        foreach ($curriculumLinks as $linkPayload) {
            $version->curriculumLinks()->create([
                'curriculum_id' => $linkPayload['curriculum_id'] ?? null,
                'curriculum_world_id' => $linkPayload['curriculum_world_id'] ?? null,
                'curriculum_unit_id' => $linkPayload['curriculum_unit_id'] ?? null,
                'curriculum_lesson_id' => $linkPayload['curriculum_lesson_id'] ?? null,
                'lesson_stage_id' => $linkPayload['lesson_stage_id'] ?? null,
            ]);
        }

        $version->skills()->sync(
            collect($skillIds)->mapWithKeys(static fn (int $skillId): array => [$skillId => ['emphasis_level' => 1]])->all(),
        );

        $version->forceFill(['total_points' => $totalPoints])->save();

        return $version->fresh(['items.options', 'curriculumLinks', 'skills']);
    }

    public function ensureEditableVersion(Assignment $assignment, User $actor): AssignmentVersion
    {
        $assignment->loadMissing(['currentVersion.items.options', 'versions']);

        if ($assignment->currentVersion?->isPublished()) {
            return $this->duplicateForEditing($assignment->currentVersion, $actor);
        }

        if ($assignment->currentVersion instanceof AssignmentVersion) {
            return $assignment->currentVersion;
        }

        return $this->createDraft([
            'assignment_type' => $assignment->assignment_type instanceof AssignmentType
                ? $assignment->assignment_type->value
                : (string) $assignment->assignment_type,
        ], $actor);
    }
}
