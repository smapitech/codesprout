<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\LearningClass;
use App\Models\LearnerWebpageProject;
use App\Models\User;
use App\Services\Assignments\AssignmentReportService;
use App\Enums\HtmlProjectStatus;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(AssignmentReportService $assignmentReports): Response
    {
        $teacher = request()->user();
        abort_unless($teacher, 403);

        $classes = $teacher->teachingClasses()
            ->with(['learners.childProfile'])
            ->withCount('learners')
            ->orderBy('sort_order')
            ->get();

        $learners = $classes
            ->flatMap(static fn (LearningClass $class): Collection => $class->learners)
            ->unique('id')
            ->values();

        $childIds = $learners->pluck('id');
        $markingQueue = $assignmentReports->teacherMarkingQueue($teacher);
        $htmlReviews = config('codesprout.features.html_learning_engine') && config('codesprout.features.html_teacher_review')
            ? LearnerWebpageProject::query()
                ->with(['child:id,name', 'templateVersion.template:id,title'])
                ->whereIn('child_id', $childIds)
                ->whereIn('status', [HtmlProjectStatus::AwaitingReview->value, HtmlProjectStatus::Resubmitted->value])
                ->latest('submitted_at')
                ->limit(6)
                ->get()
            : collect();

        return Inertia::render('teacher/dashboard', [
            'role' => RoleName::Teacher->value,
            'assignedClasses' => $classes->map(static fn (LearningClass $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->class_code,
                'learners_count' => $class->learners_count,
            ]),
            'assignedLearners' => $learners->map(static fn (User $learner): array => [
                'id' => $learner->id,
                'name' => $learner->name,
                'learner_id' => $learner->childProfile?->learner_id,
            ]),
            'pendingWork' => [
                'count' => $markingQueue->count() + $htmlReviews->count(),
                'assignments' => $markingQueue->take(6)->values(),
                'htmlProjects' => $htmlReviews->map(fn (LearnerWebpageProject $project): array => [
                    'uuid' => $project->uuid,
                    'child' => $project->child?->name,
                    'title' => $project->title,
                    'template' => $project->templateVersion?->template?->title,
                    'status' => $project->status->value,
                    'submittedAt' => $project->submitted_at?->toIso8601String(),
                    'reviewHref' => route('teacher.html.projects.review', $project, absolute: false),
                ]),
            ],
            'quickActions' => [
                ['label' => 'Create assignment', 'href' => route('teacher.assignments.create', absolute: false)],
                ['label' => 'Review submissions', 'href' => route('teacher.assignments.index', absolute: false)],
                ...(config('codesprout.features.html_learning_engine') && config('codesprout.features.html_teacher_review') ? [['label' => 'HTML projects', 'href' => route('teacher.html.index', absolute: false)]] : []),
                ['label' => 'Learner progress', 'href' => route('teacher.progress.index', absolute: false)],
            ],
        ]);
    }
}
