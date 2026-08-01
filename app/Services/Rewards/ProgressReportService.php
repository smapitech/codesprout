<?php

namespace App\Services\Rewards;

use App\Enums\BadgeAwardStatus;
use App\Models\BadgeAward;
use App\Models\Celebration;
use App\Models\CurriculumProgressRecord;
use App\Models\LearnerProgressProfile;
use App\Models\LearningClass;
use App\Models\RewardLedgerEntry;
use App\Models\SkillProgressRecord;
use App\Models\User;
use Illuminate\Support\Collection;

class ProgressReportService
{
    public function __construct(private readonly ExperienceLevelService $levels) {}

    /**
     * @return array<string, mixed>
     */
    public function childSummary(User $child): array
    {
        $profile = LearnerProgressProfile::query()->with('currentLevel')->firstOrCreate(['child_id' => $child->getKey()]);
        $nextLevel = $this->levels->nextLevelForExperience($profile->total_experience);
        $recentBadges = BadgeAward::query()
            ->with('badge')
            ->where('child_id', $child->getKey())
            ->where('status', BadgeAwardStatus::Earned)
            ->latest('awarded_at')
            ->limit(6)
            ->get();

        return [
            'profile' => [
                'total_stars' => $profile->total_stars,
                'total_experience' => $profile->total_experience,
                'current_level' => $profile->currentLevel?->name ?? 'Curious Sprout',
                'current_level_number' => $profile->currentLevel?->level_number ?? 1,
                'next_level' => $nextLevel?->name,
                'xp_to_next_level' => $nextLevel ? max(0, $nextLevel->xp_threshold - $profile->total_experience) : 0,
                'completed_missions' => $profile->completed_missions,
                'completed_lessons' => $profile->completed_lessons,
                'completed_units' => $profile->completed_units,
                'completed_worlds' => $profile->completed_worlds,
                'current_streak' => $profile->current_streak,
                'longest_streak' => $profile->longest_streak,
            ],
            'skills' => SkillProgressRecord::query()
                ->where('child_id', $child->getKey())
                ->orderByDesc('current_mastery')
                ->limit(8)
                ->get()
                ->map(fn (SkillProgressRecord $skill): array => [
                    'name' => str($skill->skill_slug)->replace('-', ' ')->title()->toString(),
                    'slug' => $skill->skill_slug,
                    'mastery' => $skill->current_mastery,
                    'label' => str($skill->mastery_label->value)->replace('_', ' ')->title()->toString(),
                    'aria_label' => str($skill->skill_slug)->replace('-', ' ')->title().' progress: '.$skill->current_mastery.' percent complete',
                ]),
            'recent_badges' => $recentBadges->map(fn (BadgeAward $award): array => [
                'id' => $award->uuid,
                'name' => $award->badge_snapshot['name'] ?? $award->badge?->name,
                'description' => $award->badge_snapshot['short_description'] ?? $award->badge?->short_description,
                'image' => $award->badge_snapshot['badge_image_path'] ?? $award->badge?->badge_image_path,
                'alt' => $award->badge_snapshot['alt_text'] ?? $award->badge?->alt_text,
                'awarded_at' => $award->awarded_at?->toIso8601String(),
            ]),
            'celebrations' => Celebration::query()
                ->where('child_id', $child->getKey())
                ->whereNull('acknowledged_at')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (Celebration $celebration): array => [
                    'id' => $celebration->uuid,
                    'type' => $celebration->celebration_type->value,
                    'heading' => $celebration->heading,
                    'message' => $celebration->message,
                    'reward_summary' => $celebration->reward_summary,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function teacherClassSummary(User $teacher): array
    {
        $classes = $teacher->teachingClasses()->with(['learners.childProfile'])->get();
        $learners = $classes->flatMap(fn (LearningClass $class): Collection => $class->learners)->unique('id')->values();
        $profiles = LearnerProgressProfile::query()->with('currentLevel')->whereIn('child_id', $learners->pluck('id'))->get()->keyBy('child_id');

        return [
            'classes' => $classes->map(fn (LearningClass $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'learners_count' => $class->learners->count(),
            ]),
            'learners' => $learners->map(fn (User $child): array => [
                'id' => $child->id,
                'name' => $child->name,
                'learner_id' => $child->childProfile?->learner_id,
                'level' => $profiles[$child->id]?->currentLevel?->name ?? 'Curious Sprout',
                'stars' => $profiles[$child->id]?->total_stars ?? 0,
                'experience' => $profiles[$child->id]?->total_experience ?? 0,
                'streak' => $profiles[$child->id]?->current_streak ?? 0,
                'completed_missions' => $profiles[$child->id]?->completed_missions ?? 0,
                'last_learning_date' => $profiles[$child->id]?->last_learning_date?->toDateString(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parentSummary(User $parent): array
    {
        $children = $parent->children()->with('childProfile')->get();

        return [
            'children' => $children->map(function (User $child): array {
                $summary = $this->childSummary($child);

                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'learner_id' => $child->childProfile?->learner_id,
                    'avatar_url' => $child->avatar_url,
                    'profile' => $summary['profile'],
                    'recent_badges' => $summary['recent_badges'],
                    'skills' => $summary['skills'],
                ];
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function administratorSummary(): array
    {
        return [
            'totals' => [
                'profiles' => LearnerProgressProfile::query()->count(),
                'ledger_entries' => RewardLedgerEntry::query()->count(),
                'badges_awarded' => BadgeAward::query()->count(),
                'curriculum_progress_records' => CurriculumProgressRecord::query()->count(),
                'skill_progress_records' => SkillProgressRecord::query()->count(),
            ],
            'recent_awards' => RewardLedgerEntry::query()
                ->with('badge')
                ->latest('awarded_at')
                ->limit(10)
                ->get()
                ->map(fn (RewardLedgerEntry $entry): array => [
                    'id' => $entry->uuid,
                    'reward_type' => $entry->reward_type->value,
                    'amount' => $entry->amount,
                    'reason' => $entry->reason,
                    'awarded_at' => $entry->awarded_at?->toIso8601String(),
                ]),
        ];
    }
}
