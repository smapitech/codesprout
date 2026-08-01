<?php

namespace App\Services\Html;

use App\Enums\HtmlProjectStatus;
use App\Models\HtmlAttempt;
use App\Models\HtmlExercise;
use App\Models\HtmlValidationResult;
use App\Models\LearnerWebpageProject;
use App\Models\ProjectShowcaseEntry;
use App\Models\TypingKeyStatistic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HtmlReportService
{
    public function adminSummary(): array
    {
        return [
            'publishedExercises' => HtmlExercise::query()->where('status', 'published')->count(),
            'completedAttempts' => HtmlAttempt::query()->whereIn('status', ['completed', 'submitted'])->count(),
            'projectsAwaitingReview' => LearnerWebpageProject::query()->where('status', HtmlProjectStatus::AwaitingReview)->count(),
            'unsafeValidations' => HtmlValidationResult::query()->where('validity_status', 'unsafe')->count(),
        ];
    }

    public function childSummary(User $child): array
    {
        return [
            'completedExercises' => HtmlAttempt::query()->where('child_id', $child->id)->where('status', 'completed')->count(),
            'activeProjects' => LearnerWebpageProject::query()->where('child_id', $child->id)->whereIn('status', ['active', 'paused', 'changes_requested'])->count(),
            'approvedProjects' => LearnerWebpageProject::query()->where('child_id', $child->id)->where('status', 'completed')->count(),
            'tagsPractised' => HtmlValidationResult::query()
                ->whereHas('attempt', fn ($query) => $query->where('child_id', $child->id))
                ->latest()
                ->limit(5)
                ->get()
                ->flatMap(fn (HtmlValidationResult $result) => array_keys($result->result_summary['tags_used'] ?? []))
                ->unique()
                ->values(),
        ];
    }

    public function teacherRows(User $teacher): array
    {
        $childIds = $teacher->teachingClasses()
            ->with('learners:id,name')
            ->get()
            ->flatMap(fn ($class) => $class->learners->pluck('id'))
            ->unique()
            ->values();

        return LearnerWebpageProject::query()
            ->with(['child:id,name', 'templateVersion.template'])
            ->whereIn('child_id', $childIds)
            ->latest('updated_at')
            ->paginate(15)
            ->through(fn (LearnerWebpageProject $project): array => [
                'uuid' => $project->uuid,
                'child' => $project->child?->name,
                'title' => $project->title,
                'status' => $project->status->value,
                'template' => $project->templateVersion->template->title,
                'updatedAt' => $project->updated_at?->toDateTimeString(),
                'reviewHref' => route('teacher.html.projects.review', $project, absolute: false),
            ])
            ->toArray();
    }

    public function parentSummary(User $parent): array
    {
        $childIds = $parent->children()->pluck('users.id');
        $showcaseEnabled = (bool) config('codesprout.features.html_private_showcase');

        return [
            'projects' => LearnerWebpageProject::query()
                ->with(['child:id,name', 'showcaseEntry.revision'])
                ->whereIn('child_id', $childIds)
                ->whereIn('status', ['awaiting_review', 'completed', 'changes_requested'])
                ->latest('updated_at')
                ->limit(12)
                ->get()
                ->map(fn (LearnerWebpageProject $project): array => [
                    'child' => $project->child?->name,
                    'title' => $project->title,
                    'status' => $project->status->value,
                    'approvedPreview' => $showcaseEnabled && $project->showcaseEntry && ! $project->showcaseEntry->withdrawn_at
                        ? $project->showcaseEntry->revision?->sanitised_html
                        : null,
                ]),
            'showcaseCount' => $showcaseEnabled
                ? ProjectShowcaseEntry::query()
                    ->whereNull('withdrawn_at')
                    ->whereHas('project', fn ($query) => $query->whereIn('child_id', $childIds))
                    ->count()
                : 0,
        ];
    }

    public function readiness(User $child): array
    {
        $keys = ['<', '>', '/', '=', '"', "'", '-', '_', 'Spacebar', 'Enter', 'Backspace', 'Shift'];
        $stats = TypingKeyStatistic::query()
            ->where('child_id', $child->id)
            ->whereIn('key_identifier', $keys)
            ->get()
            ->keyBy('key_identifier');

        return collect($keys)->map(fn (string $key): array => [
            'key' => $key,
            'attempts' => (int) ($stats[$key]->attempts ?? 0),
            'recentAccuracy' => (float) ($stats[$key]->recent_accuracy ?? 0),
            'label' => ($stats[$key]->recent_accuracy ?? 0) >= 80 && ($stats[$key]->attempts ?? 0) >= 3 ? 'Confident' : 'Practising',
        ])->all();
    }

    public function usageByType(): array
    {
        return HtmlAttempt::query()
            ->join('html_exercise_versions', 'html_attempts.html_exercise_version_id', '=', 'html_exercise_versions.id')
            ->select('html_exercise_versions.exercise_type', DB::raw('count(*) as attempts'))
            ->groupBy('html_exercise_versions.exercise_type')
            ->orderBy('html_exercise_versions.exercise_type')
            ->get()
            ->all();
    }
}
