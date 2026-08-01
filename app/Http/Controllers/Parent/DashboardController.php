<?php

namespace App\Http\Controllers\Parent;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Services\Assignments\AssignmentReportService;
use App\Services\Html\HtmlReportService;
use App\Services\Rewards\ProgressReportService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(ProgressReportService $progressReports, AssignmentReportService $assignmentReports, HtmlReportService $htmlReports): Response
    {
        $parent = request()->user();
        abort_unless($parent, 403);

        $progress = $progressReports->parentSummary($parent);
        $progressByChild = collect($progress['children'])->keyBy('id');
        $children = $parent->children()
            ->with(['childProfile', 'profile', 'enrolledClasses'])
            ->get()
            ->map(static function ($child) use ($progressByChild): array {
                $profile = $progressByChild->get($child->id)['profile'] ?? [];
                $completed = (int) ($profile['completed_missions'] ?? 0);
                $available = max(1, (int) config('codesprout.curriculum.missions_per_world', 12));
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'learner_id' => $child->childProfile?->learner_id,
                    'avatar_url' => $child->avatar_url,
                    'world' => $child->enrolledClasses->first()?->name ?? 'Computer Discovery',
                    'progress' => min(100, (int) floor(($completed / $available) * 100)),
                    'level' => $profile['current_level'] ?? 'Curious Sprout',
                    'stars' => (int) ($profile['total_stars'] ?? 0),
                    'streak' => (int) ($profile['current_streak'] ?? 0),
                ];
            }) ?? collect();

        $assignments = $assignmentReports->parentAssignments($parent);
        $htmlEnabled = (bool) config('codesprout.features.html_learning_engine') && (bool) config('codesprout.features.html_parent_preview');
        $html = $htmlEnabled ? $htmlReports->parentSummary($parent) : ['projects' => collect(), 'showcaseCount' => 0];

        return Inertia::render('parent/dashboard', [
            'role' => RoleName::Parent->value,
            'children' => $children,
            'overview' => [
                'completedMissions' => collect($progress['children'])->sum(fn (array $child): int => (int) ($child['profile']['completed_missions'] ?? 0)),
                'stars' => collect($progress['children'])->sum(fn (array $child): int => (int) ($child['profile']['total_stars'] ?? 0)),
                'releasedAssignments' => $assignments->whereIn('status', ['marked', 'completed'])->count(),
                'htmlProjects' => collect($html['projects'])->count(),
            ],
            'recentAssignments' => $assignments->take(5)->values(),
            'htmlProjects' => collect($html['projects'])->take(5)->values(),
            'quickActions' => [
                ['label' => 'View progress', 'href' => route('parent.progress.index', absolute: false)],
                ['label' => 'Assignments', 'href' => route('parent.assignments.index', absolute: false)],
                ['label' => 'Typing progress', 'href' => route('parent.typing.index', absolute: false)],
                ...($htmlEnabled ? [['label' => 'HTML projects', 'href' => route('parent.html.index', absolute: false)]] : []),
            ],
        ]);
    }
}
