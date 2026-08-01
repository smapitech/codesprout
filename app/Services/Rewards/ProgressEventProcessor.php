<?php

namespace App\Services\Rewards;

use App\Enums\ContentStatus;
use App\Enums\LearnerProgressStatus;
use App\Enums\MasteryLabel;
use App\Enums\ProgressEventStatus;
use App\Models\AssignmentAttempt;
use App\Models\CurriculumLesson;
use App\Models\CurriculumProgressRecord;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\ProgressEvent;
use App\Models\RewardRule;
use App\Models\Skill;
use App\Models\SkillProgressRecord;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgressEventProcessor
{
    public function __construct(
        private readonly RewardAwardService $awards,
        private readonly StreakService $streaks,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): ProgressEvent
    {
        $idempotencyKey = (string) ($payload['idempotency_key'] ?? sha1(json_encode([
            $payload['event_type'] ?? '',
            $payload['child_id'] ?? '',
            $payload['source_type'] ?? '',
            $payload['source_id'] ?? '',
        ])));

        return DB::transaction(function () use ($payload, $idempotencyKey): ProgressEvent {
            $event = ProgressEvent::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if ($event && $event->status === ProgressEventStatus::Processed) {
                return $event->fresh();
            }

            $event ??= ProgressEvent::query()->create([
                'event_type' => (string) $payload['event_type'],
                'child_id' => (int) $payload['child_id'],
                'source_type' => (string) $payload['source_type'],
                'source_id' => (int) $payload['source_id'],
                'curriculum_id' => $payload['curriculum_id'] ?? null,
                'curriculum_world_id' => $payload['curriculum_world_id'] ?? null,
                'curriculum_unit_id' => $payload['curriculum_unit_id'] ?? null,
                'curriculum_lesson_id' => $payload['curriculum_lesson_id'] ?? null,
                'lesson_stage_id' => $payload['lesson_stage_id'] ?? null,
                'skill_id' => $payload['skill_id'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
                'performance_summary' => Arr::only($payload['performance_summary'] ?? [], [
                    'accuracy',
                    'score',
                    'maximum_score',
                    'completion_status',
                    'difficulty',
                    'hints_used',
                    'completion_time',
                ]),
                'metadata' => Arr::only($payload['metadata'] ?? [], [
                    'timezone',
                    'source_slug',
                    'skill_slug',
                    'preview',
                    'released_to_parent',
                ]),
                'actor_id' => $payload['actor_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'status' => ProgressEventStatus::Pending,
            ]);

            if (($event->metadata['preview'] ?? false) === true) {
                $event->forceFill(['status' => ProgressEventStatus::Ignored, 'processed_at' => now()])->save();

                return $event->fresh();
            }

            $child = User::query()->findOrFail($event->child_id);
            $profile = $this->awards->profileFor($child);
            $this->streaks->recordQualifyingDay($child, $event->occurred_at, $profile, $event->metadata['timezone'] ?? null);
            $this->recordCurriculumProgress($event);
            $this->recordSkillProgress($event);

            $this->matchingRules($event)->each(fn (RewardRule $rule) => $this->awards->awardForRule($event, $rule));

            $event->forceFill(['status' => ProgressEventStatus::Processed, 'processed_at' => now()])->save();

            return $event->fresh();
        });
    }

    public function fromAssignmentCompleted(AssignmentAttempt $attempt): ProgressEvent
    {
        $attempt->loadMissing(['allocation.assignmentVersion.skills', 'allocation.assignmentVersion.curriculumLinks']);
        $version = $attempt->allocation->assignmentVersion;
        $link = $version->curriculumLinks->first();

        return $this->process([
            'event_type' => 'assignment.completed',
            'child_id' => $attempt->child_id,
            'source_type' => AssignmentAttempt::class,
            'source_id' => $attempt->getKey(),
            'curriculum_id' => $link?->curriculum_id,
            'curriculum_world_id' => $link?->curriculum_world_id,
            'curriculum_unit_id' => $link?->curriculum_unit_id,
            'curriculum_lesson_id' => $link?->curriculum_lesson_id,
            'lesson_stage_id' => $link?->lesson_stage_id,
            'skill_id' => $version->skills->first()?->id,
            'occurred_at' => $attempt->submitted_at ?? now(),
            'performance_summary' => [
                'score' => (float) $attempt->final_score,
                'maximum_score' => (float) $attempt->maximum_score,
                'accuracy' => $attempt->maximum_score > 0 ? round(((float) $attempt->final_score / (float) $attempt->maximum_score) * 100, 2) : null,
            ],
            'idempotency_key' => 'assignment.completed:'.$attempt->getKey(),
        ]);
    }

    public function fromGameCompleted(GameSession $session, ?GameResult $result = null): ProgressEvent
    {
        $session->loadMissing(['gameVersion.definition', 'lessonStage.lesson.unit.world.curriculum', 'lessonStage.skills', 'result']);
        $result ??= $session->result;
        $stage = $session->lessonStage;

        return $this->process([
            'event_type' => 'game.completed',
            'child_id' => $session->child_id,
            'source_type' => GameSession::class,
            'source_id' => $session->getKey(),
            'curriculum_id' => $stage?->lesson?->unit?->world?->curriculum_id,
            'curriculum_world_id' => $stage?->lesson?->unit?->world_id,
            'curriculum_unit_id' => $stage?->lesson?->unit_id,
            'curriculum_lesson_id' => $stage?->lesson_id,
            'lesson_stage_id' => $stage?->getKey(),
            'skill_id' => $stage?->skills?->first()?->id,
            'occurred_at' => $session->completed_at ?? now(),
            'performance_summary' => [
                'accuracy' => $result ? (float) $result->accuracy : null,
                'score' => $result ? (float) $result->score : null,
                'maximum_score' => $result ? (float) $result->maximum_score : null,
                'completion_status' => $result?->completion_status?->value,
                'difficulty' => $session->difficulty?->value,
                'hints_used' => $result?->hints_used,
                'completion_time' => $result?->completion_time,
            ],
            'metadata' => [
                'source_slug' => $session->gameVersion->definition->slug,
                'released_to_parent' => (bool) ($result?->released_to_parent),
            ],
            'idempotency_key' => 'game.completed:'.$session->getKey(),
        ]);
    }

    /**
     * @return Collection<int, RewardRule>
     */
    private function matchingRules(ProgressEvent $event)
    {
        return RewardRule::query()
            ->with('badge')
            ->where('status', ContentStatus::Published)
            ->where('event_type', $event->event_type)
            ->where(function ($query) use ($event): void {
                $query->whereNull('source_type')->orWhere('source_type', $event->source_type);
            })
            ->where(function ($query) use ($event): void {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', $event->occurred_at);
            })
            ->where(function ($query) use ($event): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $event->occurred_at);
            })
            ->orderBy('priority')
            ->get()
            ->filter(fn (RewardRule $rule): bool => $this->conditionsPass($event, $rule->eligibility_conditions ?? []));
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function conditionsPass(ProgressEvent $event, array $conditions): bool
    {
        $summary = $event->performance_summary ?? [];
        $metadata = $event->metadata ?? [];

        if (isset($conditions['minimum_accuracy']) && (float) ($summary['accuracy'] ?? 0) < (float) $conditions['minimum_accuracy']) {
            return false;
        }

        if (isset($conditions['maximum_hints']) && (int) ($summary['hints_used'] ?? 0) > (int) $conditions['maximum_hints']) {
            return false;
        }

        if (isset($conditions['source_slug']) && ($metadata['source_slug'] ?? null) !== $conditions['source_slug']) {
            return false;
        }

        if (isset($conditions['requires_completion_status']) && ($summary['completion_status'] ?? null) !== $conditions['requires_completion_status']) {
            return false;
        }

        if (isset($conditions['skill_slug']) && ($metadata['skill_slug'] ?? null) !== $conditions['skill_slug']) {
            return false;
        }

        return true;
    }

    private function recordCurriculumProgress(ProgressEvent $event): void
    {
        if (! $event->curriculum_id && ! $event->curriculum_world_id && ! $event->curriculum_lesson_id && ! $event->lesson_stage_id) {
            return;
        }

        $keys = [
            'child_id' => $event->child_id,
            'curriculum_id' => $event->curriculum_id,
            'curriculum_world_id' => $event->curriculum_world_id,
            'curriculum_unit_id' => $event->curriculum_unit_id,
            'curriculum_lesson_id' => $event->curriculum_lesson_id,
            'lesson_stage_id' => $event->lesson_stage_id,
        ];

        CurriculumProgressRecord::query()->updateOrCreate($keys, [
            'status' => LearnerProgressStatus::Completed,
            'completion_percentage' => 100,
            'completed_required_items' => 1,
            'total_required_items' => 1,
            'started_at' => $event->occurred_at,
            'completed_at' => $event->occurred_at,
            'last_activity_at' => $event->occurred_at,
            'calculated_at' => now(),
        ]);

        if ($event->curriculum_lesson_id) {
            $lesson = CurriculumLesson::query()->with('unit.world')->find($event->curriculum_lesson_id);
            if ($lesson) {
                $this->upsertRollup($event->child_id, [
                    'curriculum_id' => $lesson->unit->world->curriculum_id,
                    'curriculum_world_id' => $lesson->unit->world_id,
                    'curriculum_unit_id' => $lesson->unit_id,
                    'curriculum_lesson_id' => $lesson->getKey(),
                    'lesson_stage_id' => null,
                ], $event);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $keys
     */
    private function upsertRollup(int $childId, array $keys, ProgressEvent $event): void
    {
        CurriculumProgressRecord::query()->updateOrCreate(array_merge(['child_id' => $childId], $keys), [
            'status' => LearnerProgressStatus::Completed,
            'completion_percentage' => 100,
            'completed_required_items' => 1,
            'total_required_items' => 1,
            'completed_at' => $event->occurred_at,
            'last_activity_at' => $event->occurred_at,
            'calculated_at' => now(),
        ]);
    }

    private function recordSkillProgress(ProgressEvent $event): void
    {
        $skill = $event->skill_id ? Skill::query()->find($event->skill_id) : null;
        $skillSlug = $skill?->slug ?? ($event->metadata['skill_slug'] ?? null);

        if (! $skillSlug) {
            return;
        }

        $accuracy = (float) ($event->performance_summary['accuracy'] ?? 75);
        $increment = $accuracy >= 90 ? 15 : ($accuracy >= 70 ? 10 : 5);

        $record = SkillProgressRecord::query()->firstOrCreate([
            'child_id' => $event->child_id,
            'skill_slug' => $skillSlug,
            'curriculum_context' => $event->curriculum_world_id ? 'world:'.$event->curriculum_world_id : 'general',
        ], [
            'skill_id' => $skill?->getKey(),
            'current_mastery' => 0,
            'highest_mastery' => 0,
            'mastery_label' => MasteryLabel::GettingStarted,
        ]);

        $mastery = min(100, (int) $record->current_mastery + $increment);
        $record->forceFill([
            'skill_id' => $skill?->getKey(),
            'current_mastery' => $mastery,
            'highest_mastery' => max($mastery, (int) $record->highest_mastery),
            'mastery_label' => $this->labelFor($mastery),
            'attempts_count' => $record->attempts_count + 1,
            'completed_activities_count' => $record->completed_activities_count + 1,
            'evidence_count' => $record->evidence_count + 1,
            'last_evidence_at' => $event->occurred_at,
            'calculated_at' => now(),
            'evidence_summary' => [
                'latest_event' => $event->event_type,
                'latest_accuracy' => $accuracy,
            ],
        ])->save();
    }

    private function labelFor(int $mastery): MasteryLabel
    {
        return match (true) {
            $mastery >= 90 => MasteryLabel::Mastered,
            $mastery >= 70 => MasteryLabel::Confident,
            $mastery >= 45 => MasteryLabel::Growing,
            $mastery >= 15 => MasteryLabel::Practising,
            default => MasteryLabel::GettingStarted,
        };
    }
}
