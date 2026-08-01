<?php

namespace App\Listeners\Rewards;

use App\Events\Html\HtmlExerciseCompleted;
use App\Events\Html\WebpageProjectCompleted;
use App\Models\HtmlAttempt;
use App\Models\LearnerWebpageProject;
use App\Services\Rewards\ProgressEventProcessor;

class ProcessCompletedHtmlProgress
{
    public function __construct(private readonly ProgressEventProcessor $processor) {}

    public function handle(object $event): void
    {
        if ($event instanceof HtmlExerciseCompleted) {
            $this->handleAttempt($event->attempt);
        }

        if ($event instanceof WebpageProjectCompleted) {
            $this->handleProject($event->project);
        }
    }

    private function handleAttempt(HtmlAttempt $attempt): void
    {
        $attempt->loadMissing(['validationResult', 'exerciseVersion.exercise', 'exerciseVersion.requirements']);

        if (! $attempt->child_id || ($attempt->metadata['preview'] ?? false) === true || ! $attempt->validationResult) {
            return;
        }

        $accuracy = $attempt->validationResult->required_rule_count > 0
            ? round(($attempt->validationResult->satisfied_rule_count / $attempt->validationResult->required_rule_count) * 100, 2)
            : 100;

        $this->processor->process([
            'event_type' => 'html.completed',
            'child_id' => $attempt->child_id,
            'source_type' => HtmlAttempt::class,
            'source_id' => $attempt->id,
            'lesson_stage_id' => $attempt->lesson_stage_id,
            'occurred_at' => $attempt->completed_at ?? now(),
            'performance_summary' => [
                'accuracy' => $accuracy,
                'score' => $attempt->validationResult->satisfied_rule_count,
                'maximum_score' => max(1, $attempt->validationResult->required_rule_count),
                'completion_status' => $attempt->validationResult->validity_status->value,
                'completion_time' => $attempt->active_duration_ms,
            ],
            'metadata' => [
                'source_slug' => $attempt->exerciseVersion->exercise->slug,
                'skill_slug' => 'early-html',
                'preview' => false,
                'released_to_parent' => true,
            ],
            'idempotency_key' => 'html.completed:'.$attempt->id,
        ]);
    }

    private function handleProject(LearnerWebpageProject $project): void
    {
        $project->loadMissing(['latestRevision', 'templateVersion.template']);

        if (! $project->child_id || ($project->metadata['preview'] ?? false) === true) {
            return;
        }

        $this->processor->process([
            'event_type' => 'html.project.completed',
            'child_id' => $project->child_id,
            'source_type' => LearnerWebpageProject::class,
            'source_id' => $project->id,
            'lesson_stage_id' => $project->lesson_stage_id,
            'occurred_at' => $project->completed_at ?? now(),
            'performance_summary' => [
                'accuracy' => 100,
                'score' => 1,
                'maximum_score' => 1,
                'completion_status' => 'completed',
            ],
            'metadata' => [
                'source_slug' => $project->templateVersion->template->slug,
                'skill_slug' => 'webpage-builder',
                'preview' => false,
                'released_to_parent' => true,
            ],
            'idempotency_key' => 'html.project.completed:'.$project->id,
        ]);
    }
}
