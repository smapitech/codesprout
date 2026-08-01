<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AcademicCohort;
use App\Models\ApplicationSetting;
use App\Models\AssignmentAttempt;
use App\Models\AuditLog;
use App\Models\HtmlAttempt;
use App\Models\LearnerWebpageProject;
use App\Models\LearningClass;
use App\Models\User;
use App\Enums\AttemptStatus;
use App\Enums\HtmlProjectStatus;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $htmlEnabled = (bool) config('codesprout.features.html_learning_engine');
        $recentActivity = AuditLog::query()
            ->with('actor')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(static function (AuditLog $log): array {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor?->name ?? 'System',
                    'subject' => $log->subject_type ? class_basename($log->subject_type) : 'System',
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('admin/dashboard', [
            'role' => RoleName::Administrator->value,
            'totals' => [
                'users' => User::query()->count(),
                'administrators' => User::role(RoleName::Administrator->value)->count(),
                'teachers' => User::role(RoleName::Teacher->value)->count(),
                'parents' => User::role(RoleName::Parent->value)->count(),
                'children' => User::role(RoleName::Child->value)->count(),
            ],
            'classes' => [
                'total' => LearningClass::query()->count(),
                'active' => LearningClass::query()->where('is_active', true)->count(),
                'cohorts' => AcademicCohort::query()->count(),
                'current_cohort' => AcademicCohort::query()->where('is_current', true)->value('name'),
            ],
            'setupStatus' => [
                'configuredSettings' => ApplicationSetting::query()->count(),
                'platformReady' => ApplicationSetting::query()->exists() && AcademicCohort::query()->exists(),
            ],
            'operations' => [
                'assignmentReviews' => AssignmentAttempt::query()->whereIn('status', [AttemptStatus::Submitted->value, AttemptStatus::AwaitingReview->value])->count(),
                'htmlProjectReviews' => $htmlEnabled ? LearnerWebpageProject::query()->whereIn('status', [HtmlProjectStatus::AwaitingReview->value, HtmlProjectStatus::Resubmitted->value])->count() : 0,
                'htmlCompletionsToday' => $htmlEnabled ? HtmlAttempt::query()->where('status', 'completed')->whereDate('completed_at', today())->count() : 0,
                'childrenWithoutClass' => User::role(RoleName::Child->value)->whereDoesntHave('enrolledClasses')->count(),
                'childrenWithoutParent' => User::role(RoleName::Child->value)->whereDoesntHave('parents')->count(),
                'teachersWithoutClass' => User::role(RoleName::Teacher->value)->whereDoesntHave('teachingClasses')->count(),
            ],
            'quickActions' => [
                ['label' => 'Manage accounts & classes', 'description' => 'Create users and connect teachers, parents and children.', 'href' => route('admin.school.index', absolute: false)],
                ...(config('codesprout.features.html_learning_engine') ? [['label' => 'Review HTML engine', 'description' => 'Manage published exercises, templates and safety reports.', 'href' => route('admin.html.index', absolute: false)]] : []),
                ['label' => 'Manage assignments', 'description' => 'Publish and allocate work to authorised classes.', 'href' => route('admin.assignments.index', absolute: false)],
                ['label' => 'Curriculum builder', 'description' => 'Maintain worlds, units, lessons and activities.', 'href' => route('admin.curriculum.index', absolute: false)],
            ],
            'recentActivity' => $recentActivity,
        ]);
    }
}
