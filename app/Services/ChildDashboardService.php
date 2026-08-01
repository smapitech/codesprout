<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\ContentStatus;
use App\Models\AssignmentFeedback;
use App\Models\HtmlExercise;
use App\Models\LearnerWebpageProject;
use App\Models\LearningClass;
use App\Models\TypingExercise;
use App\Models\User;
use App\Services\Assignments\AssignmentReportService;
use App\Services\Rewards\ProgressReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ChildDashboardService
{
    public function __construct(
        private readonly ProgressReportService $progressReports,
        private readonly AssignmentReportService $assignmentReports,
    ) {}

    /**
     * Build the dashboard payload for a child learner.
     *
     * @return array<string, mixed>
     */
    public function build(User $child): array
    {
        $child->loadMissing([
            'profile',
            'childProfile',
            'enrolledClasses.cohort',
            'enrolledClasses.teachers',
            'enrolledClasses.teachers.profile',
        ]);

        $displayName = $child->profile?->display_name ?? $child->name ?? 'Explorer';
        $firstName = $child->profile?->first_name ?? Str::of($displayName)->before(' ')->toString();
        $primaryClass = $this->primaryClass($child);
        $worldName = $primaryClass?->name ?? config('codesprout.curriculum.default_world_name');
        $worldNumber = $this->worldNumber($worldName);
        $missionsAvailable = (int) config('codesprout.curriculum.missions_per_world', 12);
        $overallProgress = 0;
        $missionsCompleted = 0;
        $worldCompletion = $missionsAvailable > 0 ? (int) floor(($missionsCompleted / $missionsAvailable) * 100) : 0;
        $currentLevel = 1;
        $streakDays = 0;
        $starsEarned = 0;
        $mouseControl = 0;
        $typingAccuracy = 0;
        $codingSymbols = 0;
        $primaryTeacher = $primaryClass?->teachers
            ?->sortByDesc(static fn (User $teacher): int => (int) ($teacher->pivot->is_primary_teacher ?? false))
            ->first();
        $releasedFeedback = AssignmentFeedback::query()
            ->with('teacher:id,name')
            ->where('visible_to_child', true)
            ->whereHas('attempt', fn ($query) => $query->where('child_id', $child->id))
            ->latest()
            ->first();
        $teacherName = $releasedFeedback?->teacher?->name ?? $primaryTeacher?->name ?? 'Your teacher';
        $rewardSummary = $this->progressReports->childSummary($child);
        $profile = $rewardSummary['profile'];
        $skills = collect($rewardSummary['skills']);
        $latestBadge = collect($rewardSummary['recent_badges'])->first();
        $overallProgress = min(100, (int) floor(((int) $profile['completed_missions'] / max(1, $missionsAvailable)) * 100));
        $missionsCompleted = min($missionsAvailable, (int) $profile['completed_missions']);
        $worldCompletion = $missionsAvailable > 0 ? (int) floor(($missionsCompleted / $missionsAvailable) * 100) : 0;
        $currentLevel = (int) $profile['current_level_number'];
        $streakDays = (int) $profile['current_streak'];
        $starsEarned = (int) $profile['total_stars'];
        $mouseControl = (int) ($skills->firstWhere('slug', 'mouse-pointing')['mastery'] ?? $skills->firstWhere('slug', 'single-clicking')['mastery'] ?? $mouseControl);
        $typingAccuracy = (int) ($skills->firstWhere('slug', 'letter-typing')['mastery'] ?? $typingAccuracy);
        $codingSymbols = (int) ($skills->firstWhere('slug', 'coding-symbol-recognition')['mastery'] ?? $codingSymbols);
        $dashboardMissions = $this->dashboardMissions($child);

        return [
            'role' => RoleName::Child->value,
            'branding' => [
                'mark' => $this->imageAsset(
                    name: 'CodeSprout-Brand-Mark',
                    alt: 'CodeSprout sprout and coding-braces symbol.',
                    width: 1254,
                    height: 1254,
                    fit: 'contain',
                    priority: true,
                ),
                'mascot' => $this->imageAsset(
                    name: 'CodeSprout-Sprout-Mascot',
                    alt: '',
                    width: 1254,
                    height: 1254,
                    fit: 'contain',
                ),
            ],
            'child' => [
                'id' => $child->id,
                'first_name' => $firstName,
                'display_name' => $displayName,
                'avatar_url' => $child->avatar_url,
                'learner_id' => $child->childProfile?->learner_id,
                'current_level' => $currentLevel,
                'role_label' => RoleName::Child->label(),
            ],
            'currentWorld' => [
                'number' => $worldNumber,
                'name' => $worldName,
                'missions_completed' => $missionsCompleted,
                'missions_available' => $missionsAvailable,
                'completion_percent' => $worldCompletion,
                'continue_href' => $dashboardMissions->first()['href'] ?? route('child.missions.index', absolute: false),
                'banner' => $this->imageAsset(
                    name: 'CodeSprout-Dashboard-Keyboard-Island-Banner',
                    alt: 'Floating Keyboard Island with letter keys, an Enter key, a robot guide and a learning-stage path.',
                    width: 1942,
                    height: 809,
                    fit: 'cover',
                    priority: true,
                ),
            ],
            'missions' => $dashboardMissions->values(),
            'progress' => [
                'overall' => $overallProgress,
                'mouse_control' => $mouseControl,
                'typing_accuracy' => $typingAccuracy,
                'coding_symbols' => $codingSymbols,
            ],
            'summary' => [
                'current_level' => $currentLevel,
                'missions_completed' => $missionsCompleted,
                'missions_available' => $missionsAvailable,
                'overall_progress' => $overallProgress,
                'streak_days' => $streakDays,
                'stars' => $starsEarned,
            ],
            'progressPath' => $this->progressPath($missionsCompleted),
            'badge' => [
                'label' => $latestBadge ? 'Recently earned!' : 'Keep growing!',
                'title' => $latestBadge['name'] ?? 'Your first badge is waiting',
                'earned_at' => ($latestBadge['awarded_at'] ?? null) ? Carbon::parse($latestBadge['awarded_at'])->toFormattedDateString() : 'Waiting for your first badge',
                'image' => $this->imageAsset(
                    name: 'CodeSprout-Badge-Key-Explorer',
                    alt: $latestBadge['alt'] ?? 'CodeSprout achievement badge.',
                    width: 1254,
                    height: 1254,
                    fit: 'contain',
                ),
            ],
            'streak' => [
                'label' => 'Learning streak',
                'days' => $streakDays,
                'description' => 'Consistent practice helps children move from watching to building with confidence.',
            ],
            'teacherFeedback' => [
                'headline' => "From {$teacherName}",
                'message' => $releasedFeedback?->feedback_text ?? 'No teacher feedback has been released yet. Complete a mission and your teacher can share a helpful note here.',
                'guide' => $this->imageAsset(
                    name: 'CodeSprout-Robot-Guide',
                    alt: 'Friendly CodeSprout robot waving.',
                    width: 1254,
                    height: 1254,
                    fit: 'contain',
                ),
            ],
        ];
    }

    private function primaryClass(User $child): ?LearningClass
    {
        return $child->enrolledClasses()
            ->with(['cohort', 'teachers'])
            ->orderByPivot('is_primary_class', 'desc')
            ->orderBy('sort_order')
            ->first();
    }

    private function dashboardMissions(User $child)
    {
        $assignmentMissions = $this->assignmentReports->childMissions($child);
        $missions = collect($assignmentMissions['continue'] ?? [])
            ->merge($assignmentMissions['today'] ?? [])
            ->unique('id')
            ->take(3)
            ->values()
            ->map(fn (array $mission, int $index): array => $this->missionCard(
                id: 'assignment-'.$mission['id'],
                number: $index + 1,
                title: $mission['title'],
                category: $mission['category'],
                duration: ((int) $mission['estimated_minutes']).' min',
                stars: (int) $mission['stars'],
                href: $mission['continue_href'],
            ));

        $htmlEnabled = (bool) config('codesprout.features.html_learning_engine');
        $projectsEnabled = $htmlEnabled && (bool) config('codesprout.features.html_project_assignments');
        $activeProject = $projectsEnabled
            ? LearnerWebpageProject::query()
                ->where('child_id', $child->id)
                ->whereIn('status', ['draft', 'active', 'paused', 'changes_requested'])
                ->latest('updated_at')
                ->first()
            : null;
        if ($missions->count() < 3 && $activeProject) {
            $missions->push($this->missionCard(
                id: 'html-project-'.$activeProject->uuid,
                number: $missions->count() + 1,
                title: $activeProject->title,
                category: 'HTML project',
                duration: 'Continue project',
                stars: 0,
                href: route('child.html.projects.show', $activeProject, absolute: false),
            ));
        }

        $htmlExercise = $htmlEnabled && (bool) config('codesprout.features.html_code_editor')
            ? HtmlExercise::query()->where('status', ContentStatus::Published)->orderBy('title')->first()
            : null;
        if ($missions->count() < 3 && $htmlExercise) {
            $missions->push($this->missionCard(
                id: 'html-'.$htmlExercise->slug,
                number: $missions->count() + 1,
                title: $htmlExercise->title,
                category: 'HTML',
                duration: 'Published activity',
                stars: 0,
                href: route('child.html.index', absolute: false),
            ));
        }

        $typingExercise = TypingExercise::query()->where('status', ContentStatus::Published)->orderBy('title')->first();
        if ($missions->count() < 3 && $typingExercise) {
            $missions->push($this->missionCard(
                id: 'typing-'.$typingExercise->slug,
                number: $missions->count() + 1,
                title: $typingExercise->title,
                category: 'Typing',
                duration: 'Published practice',
                stars: 0,
                href: route('child.typing.index', absolute: false),
            ));
        }

        return $missions->take(3);
    }

    private function missionCard(string $id, int $number, string $title, string $category, string $duration, int $stars, string $href): array
    {
        $isHtml = Str::contains(Str::lower($category), ['html', 'code', 'web']);
        $isTyping = Str::contains(Str::lower($category), ['typing', 'keyboard', 'letter']);
        $imageName = $isHtml ? 'CodeSprout-Mission-Build-Name-Tag' : ($isTyping ? 'CodeSprout-Mission-Falling-Letters' : 'CodeSprout-Mission-Enter-Key');
        $alt = $isHtml
            ? 'Colourful HTML learning blocks in the CodeSprout webpage builder.'
            : ($isTyping ? 'Colourful letters above matching keyboard keys.' : 'Large Enter key surrounded by plants and colourful flowers.');

        return [
            'id' => $id,
            'number' => $number,
            'title' => $title,
            'category' => $category,
            'duration' => $duration,
            'stars' => $stars,
            'href' => $href,
            'image' => $this->imageAsset($imageName, $alt, 1448, 1086, 'cover'),
        ];
    }

    private function worldNumber(string $worldName): int
    {
        $worlds = collect(config('codesprout.curriculum.worlds', []));
        $worldIndex = $worlds->search($worldName);

        if ($worldIndex === false) {
            return (int) config('codesprout.curriculum.default_world_index', 3);
        }

        return (int) $worldIndex;
    }

    /**
     * @return array<int, array{label: string, progress_state: string, status: string, aria_label: string}>
     */
    private function progressPath(int $missionsCompleted): array
    {
        $steps = collect(config('codesprout.learning_progression', []));

        return $steps->values()->map(static function (string $label, int $index) use ($missionsCompleted): array {
            $completedSteps = max(0, min(9, $missionsCompleted));
            $progressState = match (true) {
                $index < $completedSteps => 'completed',
                $index === $completedSteps => 'current',
                default => 'not_started',
            };
            $isUnlocked = $index <= $completedSteps;

            return [
                'label' => $label,
                'progress_state' => $progressState,
                'status' => $isUnlocked ? 'unlocked' : 'locked',
                'aria_label' => "{$label}, ".str_replace('_', ' ', $progressState).', '.($isUnlocked ? 'available' : 'locked'),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function imageAsset(string $name, string $alt, int $width, int $height, string $fit = 'contain', bool $priority = false): array
    {
        return [
            'name' => $name,
            'alt' => $alt,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
            'priority' => $priority,
            'png' => asset("assets/codesprout/original/{$name}.png"),
            'webp' => asset("assets/codesprout/webp/{$name}.webp"),
            'avif' => asset("assets/codesprout/avif/{$name}.avif"),
        ];
    }

}
