<?php

namespace App\Services\Html;

use App\Enums\ContentStatus;
use App\Models\HtmlExercise;
use App\Models\HtmlExerciseVersion;
use App\Models\HtmlTagPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HtmlExercisePublicationService
{
    public function __construct(private readonly HtmlAuditService $audit) {}

    public function createDraft(array $data, User $actor): HtmlExercise
    {
        return DB::transaction(function () use ($data, $actor): HtmlExercise {
            $exercise = HtmlExercise::query()->create([
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'title' => $data['title'],
                'exercise_type' => $data['exercise_type'],
                'description' => $data['description'] ?? null,
                'child_instructions' => $data['child_instructions'],
                'teacher_instructions' => $data['teacher_instructions'] ?? null,
                'status' => ContentStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $version = $this->createVersion($exercise, $data, 1);
            $exercise->forceFill(['current_version_id' => $version->id])->save();
            $this->audit->record('html.exercise.created', $exercise, $actor);

            return $exercise->fresh(['currentVersion.requirements']);
        });
    }

    public function createDraftFrom(HtmlExercise $exercise, array $data, User $actor): HtmlExerciseVersion
    {
        return DB::transaction(function () use ($exercise, $data, $actor): HtmlExerciseVersion {
            $next = ((int) $exercise->versions()->max('version_number')) + 1;
            $version = $this->createVersion($exercise, $data, $next);
            $exercise->forceFill([
                'title' => $data['title'] ?? $exercise->title,
                'child_instructions' => $data['child_instructions'] ?? $exercise->child_instructions,
                'current_version_id' => $version->id,
                'status' => ContentStatus::Draft,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->record('html.exercise.version_created', $version, $actor, ['previous_versions' => $next - 1]);

            return $version;
        });
    }

    public function publish(HtmlExerciseVersion $version, User $actor): HtmlExerciseVersion
    {
        return DB::transaction(function () use ($version, $actor): HtmlExerciseVersion {
            $version->loadMissing(['exercise', 'tagPolicy', 'requirements']);
            $this->validateForPublication($version);
            $version->forceFill(['status' => ContentStatus::Published, 'published_at' => now(), 'published_by' => $actor->id])->save();
            $version->exercise->forceFill(['status' => ContentStatus::Published, 'current_version_id' => $version->id, 'updated_by' => $actor->id])->save();
            $this->audit->record('html.exercise.published', $version, $actor, ['version_number' => $version->version_number]);

            return $version->fresh(['exercise', 'requirements']);
        });
    }

    public function archive(HtmlExercise $exercise, User $actor): HtmlExercise
    {
        $exercise->forceFill(['status' => ContentStatus::Archived, 'archived_at' => now(), 'updated_by' => $actor->id])->save();
        $this->audit->record('html.exercise.archived', $exercise, $actor);

        return $exercise->fresh('currentVersion');
    }

    public function validateForPublication(HtmlExerciseVersion $version): void
    {
        if ($version->tagPolicy->status !== ContentStatus::Published) {
            throw ValidationException::withMessages(['html_tag_policy_id' => 'HTML exercises need a published tag policy.']);
        }

        if ($version->requirements->where('required', true)->isEmpty()) {
            throw ValidationException::withMessages(['requirements' => 'Add at least one completion requirement.']);
        }
    }

    private function createVersion(HtmlExercise $exercise, array $data, int $versionNumber): HtmlExerciseVersion
    {
        $policy = HtmlTagPolicy::query()->findOrFail($data['html_tag_policy_id']);
        $requirements = $data['requirements'] ?? [];
        if ($requirements === []) {
            throw ValidationException::withMessages(['requirements' => 'Add at least one HTML requirement.']);
        }

        $version = $exercise->versions()->create([
            'version_number' => $versionNumber,
            'exercise_type' => $data['exercise_type'] ?? $exercise->exercise_type->value,
            'content_configuration' => $data['content_configuration'] ?? [],
            'html_tag_policy_id' => $policy->id,
            'completion_configuration' => $data['completion_configuration'] ?? ['minimum_required_rules' => 1],
            'assistance_configuration' => $data['assistance_configuration'] ?? [],
            'preview_configuration' => $data['preview_configuration'] ?? ['sandbox' => true],
            'assessment_configuration' => $data['assessment_configuration'] ?? [],
            'accessibility_configuration' => $data['accessibility_configuration'] ?? ['reduced_motion' => true],
            'status' => ContentStatus::Draft,
            'content_checksum' => hash('sha256', json_encode([$data['content_configuration'] ?? [], $requirements], JSON_THROW_ON_ERROR)),
        ]);

        foreach (array_values($requirements) as $index => $requirement) {
            $version->requirements()->create([
                'requirement_type' => $requirement['requirement_type'],
                'tag_name' => $requirement['tag_name'] ?? null,
                'attribute_name' => $requirement['attribute_name'] ?? null,
                'expected_value' => $requirement['expected_value'] ?? null,
                'minimum_count' => $requirement['minimum_count'] ?? 1,
                'maximum_count' => $requirement['maximum_count'] ?? null,
                'display_order' => $requirement['display_order'] ?? $index + 1,
                'required' => $requirement['required'] ?? true,
                'scoring_weight' => $requirement['scoring_weight'] ?? 1,
                'safe_configuration' => $requirement['safe_configuration'] ?? [],
            ]);
        }

        return $version;
    }
}
