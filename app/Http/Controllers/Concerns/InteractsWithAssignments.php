<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\AllocationStatus;
use App\Enums\AttemptStatus;
use App\Models\Assignment;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AssignmentItemOption;
use App\Models\AssignmentVersion;
use App\Services\Assignments\AssignmentPublicationService;
use App\Services\Assignments\AssignmentQuestionHandlerRegistry;
use Illuminate\Validation\ValidationException;

trait InteractsWithAssignments
{
    protected function assignmentResource(Assignment $assignment): array
    {
        $assignment->loadMissing(['currentVersion.items.options', 'versions', 'owner', 'creator']);

        $resource = [
            'id' => $assignment->id,
            'owner_id' => $assignment->owner_id,
            'created_by' => $assignment->created_by,
            'assignment_type' => $assignment->assignment_type?->value ?? (string) $assignment->assignment_type,
            'assignment_type_label' => $assignment->assignment_type?->label() ?? ucfirst((string) $assignment->assignment_type),
            'status' => $assignment->status?->value ?? (string) $assignment->status,
            'current_version_id' => $assignment->current_version_id,
            'archived_at' => $assignment->archived_at?->toIso8601String(),
            'versions_count' => $assignment->versions->count(),
            'created_at' => $assignment->created_at?->toIso8601String(),
            'updated_at' => $assignment->updated_at?->toIso8601String(),
            'owner_name' => $assignment->owner?->name ?? null,
            'creator_name' => $assignment->creator?->name ?? null,
            'current_version' => $assignment->currentVersion ? $this->versionResource($assignment->currentVersion) : null,
        ];

        return $resource;
    }

    protected function versionResource(AssignmentVersion $version, ?AssignmentQuestionHandlerRegistry $registry = null, bool $forLearner = false): array
    {
        $version->loadMissing(['items.options', 'curriculumLinks', 'skills', 'assignment']);

        $resource = [
            'id' => $version->id,
            'assignment_id' => $version->assignment_id,
            'version_number' => $version->version_number,
            'title' => $version->title,
            'short_description' => $version->short_description,
            'child_instructions' => $version->child_instructions,
            'teacher_instructions' => $version->teacher_instructions,
            'audio_instruction_path' => $version->audio_instruction_path,
            'estimated_minutes' => $version->estimated_minutes,
            'difficulty_level' => $version->difficulty_level?->value ?? (string) $version->difficulty_level,
            'total_points' => $version->total_points,
            'default_attempt_limit' => $version->default_attempt_limit,
            'feedback_mode' => $version->feedback_mode?->value ?? (string) $version->feedback_mode,
            'scoring_method' => $version->scoring_method?->value ?? (string) $version->scoring_method,
            'status' => $version->status?->value ?? (string) $version->status,
            'published_at' => $version->published_at?->toIso8601String(),
            'published_by' => $version->published_by,
            'settings' => $version->settings ?? [],
            'assignment_type' => $version->assignment?->assignment_type?->value ?? null,
            'items' => $version->items->map(function (AssignmentItem $item) use ($registry, $forLearner): array {
                return $this->itemResource($item, $registry, $forLearner);
            })->values()->all(),
            'curriculum_links' => $version->curriculumLinks->map(static fn ($link): array => [
                'id' => $link->id,
                'curriculum_id' => $link->curriculum_id,
                'curriculum_world_id' => $link->curriculum_world_id,
                'curriculum_unit_id' => $link->curriculum_unit_id,
                'curriculum_lesson_id' => $link->curriculum_lesson_id,
                'lesson_stage_id' => $link->lesson_stage_id,
            ])->values()->all(),
            'skills' => $version->skills->map(static fn ($skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'emphasis_level' => (int) ($skill->pivot->emphasis_level ?? 1),
            ])->values()->all(),
        ];

        if ($forLearner) {
            $resource['teacher_instructions'] = null;
            $resource['settings'] = [];
            $resource['published_by'] = null;
        }

        return $resource;
    }

    protected function itemResource(AssignmentItem $item, ?AssignmentQuestionHandlerRegistry $registry = null, bool $forLearner = false): array
    {
        $item->loadMissing(['options']);
        $questionType = $item->question_type?->value ?? (string) $item->question_type;

        $base = [
            'id' => $item->id,
            'assignment_version_id' => $item->assignment_version_id,
            'title' => $item->title,
            'prompt_text' => $item->prompt_text,
            'audio_prompt_path' => $item->audio_prompt_path,
            'image_path' => $item->image_path,
            'html_exercise_version_id' => $item->html_exercise_version_id,
            'project_template_version_id' => $item->project_template_version_id,
            'question_type' => $questionType,
            'interaction_type' => $item->interaction_type?->value ?? (string) $item->interaction_type,
            'points' => $item->points,
            'is_required' => $item->is_required,
            'hint_text' => $item->hint_text,
            'hint_audio_path' => $item->hint_audio_path,
            'explanation_text' => $item->explanation_text,
            'display_order' => $item->display_order,
            'configuration' => $item->configuration ?? [],
            'grading_configuration' => $item->grading_configuration ?? [],
        ];

        if ($forLearner && $registry) {
            unset($base['configuration'], $base['grading_configuration'], $base['explanation_text']);

            return array_merge($base, $registry->transformForLearner($item));
        }

        return array_merge($base, [
            'options' => $item->options->map(fn (AssignmentItemOption $option): array => [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'image_path' => $option->image_path,
                'option_value' => $option->option_value,
                'matching_key' => $option->matching_key,
                'is_correct' => $option->is_correct,
                'display_order' => $option->display_order,
            ])->values()->all(),
        ]);
    }

    protected function allocationResource(AssignmentAllocation $allocation): array
    {
        $allocation->loadMissing(['assignmentVersion.assignment', 'classroom', 'group', 'child', 'attempts']);

        return [
            'id' => $allocation->id,
            'assignment_version_id' => $allocation->assignment_version_id,
            'assigned_by' => $allocation->assigned_by,
            'class_id' => $allocation->class_id,
            'group_id' => $allocation->group_id,
            'child_id' => $allocation->child_id,
            'available_from' => $allocation->available_from?->toIso8601String(),
            'due_at' => $allocation->due_at?->toIso8601String(),
            'closes_at' => $allocation->closes_at?->toIso8601String(),
            'attempt_limit' => $allocation->attempt_limit,
            'scoring_method' => $allocation->scoring_method?->value ?? (string) $allocation->scoring_method,
            'show_score_to_child' => $allocation->show_score_to_child,
            'show_correct_answers' => $allocation->show_correct_answers,
            'allow_late_submission' => $allocation->allow_late_submission,
            'late_submission_policy' => $allocation->late_submission_policy?->value ?? (string) $allocation->late_submission_policy,
            'status' => $allocation->status?->value ?? (string) $allocation->status,
            'target_label' => $allocation->targetLabel(),
            'assignment_title' => $allocation->assignmentVersion?->title,
            'attempts_count' => $allocation->attempts->count(),
        ];
    }

    protected function attemptResource(AssignmentAttempt $attempt, bool $forLearner = false): array
    {
        $attempt->loadMissing(['allocation.assignmentVersion.items.options', 'child.childProfile', 'responses.item', 'feedback.teacher', 'rubricScores.rubricCriterion']);
        $resultReleased = ! $forLearner || (
            (bool) $attempt->allocation?->show_score_to_child
            && in_array($attempt->status, [AttemptStatus::Marked, AttemptStatus::Completed], true)
        );
        $answersReleased = $resultReleased && (bool) $attempt->allocation?->show_correct_answers;

        return [
            'id' => $attempt->id,
            'assignment_allocation_id' => $attempt->assignment_allocation_id,
            'assignment_version_id' => $attempt->assignment_version_id,
            'child_id' => $attempt->child_id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status?->value ?? (string) $attempt->status,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'last_activity_at' => $attempt->last_activity_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'auto_score' => $resultReleased ? (float) $attempt->auto_score : null,
            'manual_score' => $resultReleased ? (float) $attempt->manual_score : null,
            'final_score' => $resultReleased ? (float) $attempt->final_score : null,
            'maximum_score' => $resultReleased ? (float) $attempt->maximum_score : null,
            'time_spent_seconds' => (int) $attempt->time_spent_seconds,
            'hints_used' => (int) $attempt->hints_used,
            'is_late' => (bool) $attempt->is_late,
            'assignment_title' => $attempt->allocation?->assignmentVersion?->title,
            'child_name' => $attempt->child?->name,
            'responses' => $attempt->responses->map(fn ($response): array => [
                'id' => $response->id,
                'assignment_item_id' => $response->assignment_item_id,
                'text_response' => $response->text_response,
                'response_data' => $forLearner ? $response->response_data : $response->response_data,
                'is_correct' => $answersReleased ? $response->is_correct : ($forLearner ? null : $response->is_correct),
                'auto_score' => $resultReleased ? $response->auto_score : null,
                'manual_score' => $resultReleased ? $response->manual_score : null,
                'teacher_comment' => $forLearner ? null : $response->teacher_comment,
            ])->values()->all(),
            'feedback' => $attempt->feedback
                ->when($forLearner, fn ($feedback) => $feedback->where('visible_to_child', true))
                ->map(static fn ($feedback): array => [
                'id' => $feedback->id,
                'feedback_text' => $feedback->feedback_text,
                'feedback_type' => $feedback->feedback_type?->value ?? (string) $feedback->feedback_type,
                'returned_for_retry' => $feedback->returned_for_retry,
                'visible_to_child' => $feedback->visible_to_child,
                'visible_to_parent' => $feedback->visible_to_parent,
                'teacher_name' => $feedback->teacher?->name,
                ])->values()->all(),
            'rubric_scores' => $resultReleased ? $attempt->rubricScores->map(static fn ($score): array => [
                'id' => $score->id,
                'rubric_criterion_id' => $score->rubric_criterion_id,
                'awarded_points' => $score->awarded_points,
                'teacher_comment' => $forLearner ? null : $score->teacher_comment,
                'criterion_title' => $score->rubricCriterion?->title,
            ])->values()->all() : [],
        ];
    }

    protected function allocationStatusLabel(AssignmentAllocation $allocation): string
    {
        return match ($allocation->status) {
            AllocationStatus::Scheduled => 'Scheduled',
            AllocationStatus::Open => 'Open',
            AllocationStatus::Closed => 'Closed',
            AllocationStatus::Cancelled => 'Cancelled',
            default => ucfirst((string) $allocation->status),
        };
    }

    protected function attemptStatusLabel(AssignmentAttempt $attempt): string
    {
        return match ($attempt->status) {
            AttemptStatus::NotStarted => 'Not started',
            AttemptStatus::InProgress => 'In progress',
            AttemptStatus::Submitted => 'Submitted',
            AttemptStatus::AwaitingReview => 'Awaiting review',
            AttemptStatus::Marked => 'Marked',
            AttemptStatus::Returned => 'Returned',
            AttemptStatus::Completed => 'Completed',
            default => ucfirst((string) $attempt->status),
        };
    }

    protected function publicationValidation(AssignmentVersion $version, AssignmentPublicationService $publicationService): array
    {
        try {
            $publicationService->validateVersion($version);

            return [
                'is_publishable' => true,
                'messages' => [],
            ];
        } catch (ValidationException $exception) {
            return [
                'is_publishable' => false,
                'messages' => $exception->errors(),
            ];
        }
    }
}
