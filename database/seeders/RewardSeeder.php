<?php

namespace Database\Seeders;

use App\Enums\BadgeCategory;
use App\Enums\ContentStatus;
use App\Enums\RewardRepeatPolicy;
use App\Enums\RewardType;
use App\Models\AssignmentAttempt;
use App\Models\AuditLog;
use App\Models\BadgeDefinition;
use App\Models\GameSession;
use App\Models\LearnerLevel;
use App\Models\LearnerProgressProfile;
use App\Models\ProgressSnapshot;
use App\Models\RewardRule;
use App\Models\StreakRecord;
use App\Models\User;
use App\Services\Rewards\ProgressEventProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->clearExistingRewardData();

            $admin = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
            $teacher = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
            $children = User::role('child')->orderBy('name')->get();

            $this->seedLevels($admin);
            $badges = $this->seedBadges($admin);
            $this->seedRules($admin, $badges);

            $processor = app(ProgressEventProcessor::class);

            GameSession::query()->with('result')->whereNotNull('completed_at')->get()
                ->each(fn (GameSession $session) => $processor->fromGameCompleted($session));

            AssignmentAttempt::query()->with('allocation.assignmentVersion')->whereNotNull('submitted_at')->limit(2)->get()
                ->each(fn (AssignmentAttempt $attempt) => $processor->fromAssignmentCompleted($attempt));

            $children->each(function (User $child): void {
                LearnerProgressProfile::query()->firstOrCreate(['child_id' => $child->getKey()], ['progress_calculated_at' => now()]);
            });

            $noah = $children->firstWhere('name', 'Noah Stone');
            if ($noah) {
                StreakRecord::query()->updateOrCreate(
                    ['child_id' => $noah->getKey(), 'learning_date' => now()->subDays(5)->toDateString(), 'timezone' => config('app.timezone')],
                    [
                        'qualifying_activity_count' => 1,
                        'first_qualifying_activity_at' => now()->subDays(5),
                        'last_qualifying_activity_at' => now()->subDays(5),
                        'status' => 'ended',
                    ],
                );
                $noah->progressProfile?->forceFill(['longest_streak' => 2, 'current_streak' => 0, 'last_learning_date' => now()->subDays(5)->toDateString()])->save();
            }

            LearnerProgressProfile::query()->with('currentLevel')->get()->each(function (LearnerProgressProfile $profile): void {
                ProgressSnapshot::query()->updateOrCreate(
                    ['child_id' => $profile->child_id, 'snapshot_date' => now()->toDateString()],
                    [
                        'stars' => $profile->total_stars,
                        'experience' => $profile->total_experience,
                        'level' => $profile->currentLevel?->name,
                        'streak' => $profile->current_streak,
                        'curriculum_completion' => min(100, $profile->completed_missions * 10),
                        'skill_summary' => [],
                        'completed_worlds' => [],
                        'badges_earned' => [],
                        'generated_at' => now(),
                    ],
                );
            });

            AuditLog::query()->create([
                'actor_user_id' => $admin->getKey(),
                'action' => 'reward.seeded.phase_five',
                'subject_type' => RewardRule::class,
                'subject_id' => RewardRule::query()->first()?->getKey(),
                'metadata' => [
                    'levels' => LearnerLevel::query()->count(),
                    'badges' => BadgeDefinition::query()->count(),
                    'rules' => RewardRule::query()->count(),
                ],
                'created_at' => now(),
            ]);

            $teacher->touch();
        });
    }

    private function seedLevels(User $admin): void
    {
        $levels = [
            [1, 'Curious Sprout', 0],
            [2, 'Mouse Explorer', 100],
            [3, 'Keyboard Adventurer', 240],
            [4, 'Typing Trailblazer', 420],
            [5, 'Code Builder', 680],
            [6, 'Creative Coder', 980],
            [7, 'CodeSprout Champion', 1350],
        ];

        foreach ($levels as [$number, $name, $threshold]) {
            LearnerLevel::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'level_number' => $number,
                'xp_threshold' => $threshold,
                'description' => "Level {$number} celebrates steady CodeSprout growth.",
                'status' => ContentStatus::Published,
                'version' => 1,
                'published_at' => now(),
                'published_by' => $admin->getKey(),
            ]);
        }
    }

    /**
     * @return array<string, BadgeDefinition>
     */
    private function seedBadges(User $admin): array
    {
        $badgeRows = [
            ['Computer Explorer', BadgeCategory::ComputerSkills, 'Explored important computer parts.'],
            ['Mouse Master', BadgeCategory::MouseSkills, 'Built mouse confidence through practice.'],
            ['Click Champion', BadgeCategory::MouseSkills, 'Clicked targets carefully and accurately.'],
            ['Drag-and-Drop Gardener', BadgeCategory::MouseSkills, 'Moved learning objects into the right places.'],
            ['Scroll Adventurer', BadgeCategory::MouseSkills, 'Completed a safe scrolling journey.'],
            ['Key Explorer', BadgeCategory::KeyboardSkills, 'Found important keyboard keys.'],
            ['Enter Key Expert', BadgeCategory::KeyboardSkills, 'Practised the Enter key with confidence.'],
            ['Keyboard Pathfinder', BadgeCategory::KeyboardSkills, 'Used keyboard directions to complete a path.'],
            ['Letter Catcher', BadgeCategory::TypingSkills, 'Matched falling letters with careful key presses.'],
            ['Typing Starter', BadgeCategory::TypingSkills, 'Started typing short letters and words.'],
            ['Accuracy Star', BadgeCategory::Accuracy, 'Showed careful, accurate practice.'],
            ['Mission Finisher', BadgeCategory::LearningJourney, 'Completed a learning mission.'],
            ['World Explorer', BadgeCategory::WorldCompletion, 'Completed a learning world milestone.'],
            ['Helpful Learner', BadgeCategory::TeacherRecognition, 'Received teacher recognition for helpful learning.'],
            ['Creative Builder', BadgeCategory::Creativity, 'Created a guided CodeSprout project.'],
            ['CodeSprout Champion', BadgeCategory::LearningJourney, 'Celebrated a big CodeSprout milestone.'],
            ['Coding Symbol Explorer', BadgeCategory::CodingSkills, 'Practised safe coding symbols.'],
            ['Tag Discoverer', BadgeCategory::CodingSkills, 'Discovered how HTML tags work.'],
            ['Heading Hero', BadgeCategory::CodingSkills, 'Built a clear webpage heading.'],
            ['Webpage Builder', BadgeCategory::Creativity, 'Created a safe starter webpage.'],
            ['First Webpage Creator', BadgeCategory::Creativity, 'Completed a teacher-approved first webpage.'],
        ];

        $badges = [];
        foreach ($badgeRows as $index => [$name, $category, $description]) {
            $badges[Str::slug($name)] = BadgeDefinition::query()->create([
                'slug' => Str::slug($name),
                'name' => $name,
                'short_description' => $description,
                'long_description' => $description.' This badge is awarded from validated learning evidence.',
                'badge_category' => $category,
                'badge_image_path' => 'assets/codesprout/original/CodeSprout-Badge-Key-Explorer.png',
                'alt_text' => "{$name} CodeSprout achievement badge.",
                'qualification_type' => 'event_rule',
                'qualification_configuration' => ['event_based' => true],
                'display_order' => $index + 1,
                'repeatable' => false,
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'created_by' => $admin->getKey(),
                'updated_by' => $admin->getKey(),
            ]);
        }

        return $badges;
    }

    /**
     * @param  array<string, BadgeDefinition>  $badges
     */
    private function seedRules(User $admin, array $badges): void
    {
        $rules = [
            ['Mission completion stars', 'assignment.completed', null, RewardType::Stars, 15, RewardRepeatPolicy::OncePerSource, []],
            ['Mission completion XP', 'assignment.completed', null, RewardType::Experience, 30, RewardRepeatPolicy::OncePerSource, []],
            ['Assignment badge', 'assignment.completed', null, RewardType::Badge, 0, RewardRepeatPolicy::OncePerSource, [], 'mission-finisher'],
            ['First game completion stars', 'game.completed', null, RewardType::Stars, 10, RewardRepeatPolicy::OncePerSource, []],
            ['First game completion XP', 'game.completed', null, RewardType::Experience, 20, RewardRepeatPolicy::OncePerSource, []],
            ['Improved accuracy XP', 'game.completed', null, RewardType::Experience, 15, RewardRepeatPolicy::Repeatable, ['minimum_accuracy' => 90]],
            ['Keyboard key badge', 'game.completed', null, RewardType::Badge, 0, RewardRepeatPolicy::OncePerSource, ['source_slug' => 'find-the-enter-key'], 'key-explorer'],
            ['Typing completion stars', 'typing.completed', null, RewardType::Stars, 8, RewardRepeatPolicy::OncePerSource, ['requires_completion_status' => 'valid']],
            ['Typing completion XP', 'typing.completed', null, RewardType::Experience, 18, RewardRepeatPolicy::OncePerSource, ['requires_completion_status' => 'valid']],
            ['Typing starter badge', 'typing.completed', null, RewardType::Badge, 0, RewardRepeatPolicy::OncePerSource, ['source_slug' => 'first-three-letter-words'], 'typing-starter'],
            ['Typing accuracy badge', 'typing.completed', null, RewardType::Badge, 0, RewardRepeatPolicy::OncePerSource, ['minimum_accuracy' => 90], 'accuracy-star'],
            ['HTML completion stars', 'html.completed', null, RewardType::Stars, 10, RewardRepeatPolicy::OncePerSource, ['requires_completion_status' => 'valid']],
            ['HTML completion XP', 'html.completed', null, RewardType::Experience, 22, RewardRepeatPolicy::OncePerSource, ['requires_completion_status' => 'valid']],
            ['HTML tag badge', 'html.completed', null, RewardType::Badge, 0, RewardRepeatPolicy::OncePerSource, ['skill_slug' => 'early-html'], 'tag-discoverer'],
            ['Webpage project stars', 'html.project.completed', null, RewardType::Stars, 35, RewardRepeatPolicy::OncePerSource, []],
            ['First webpage badge', 'html.project.completed', null, RewardType::Badge, 0, RewardRepeatPolicy::OncePerSource, [], 'first-webpage-creator'],
            ['Unit completion XP', 'unit.completed', null, RewardType::Experience, 60, RewardRepeatPolicy::OncePerSource, []],
            ['World completion stars', 'world.completed', null, RewardType::Stars, 100, RewardRepeatPolicy::OncePerSource, []],
            ['Teacher recognition badge', 'teacher.recognition.granted', null, RewardType::Badge, 0, RewardRepeatPolicy::Limited, [], 'helpful-learner'],
        ];

        foreach ($rules as $rule) {
            [$name, $eventType, $sourceType, $rewardType, $amount, $repeatPolicy, $conditions] = $rule;
            $badgeSlug = $rule[7] ?? null;
            RewardRule::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'event_type' => $eventType,
                'source_type' => $sourceType,
                'eligibility_conditions' => $conditions,
                'reward_type' => $rewardType,
                'reward_amount' => $amount,
                'badge_definition_id' => isset($badgeSlug) ? $badges[$badgeSlug]?->getKey() : null,
                'repeat_policy' => $repeatPolicy,
                'maximum_awards' => $repeatPolicy === RewardRepeatPolicy::Limited ? 3 : null,
                'daily_cap' => $rewardType === RewardType::Experience ? 150 : null,
                'priority' => 100,
                'status' => ContentStatus::Published,
                'version' => 1,
                'created_by' => $admin->getKey(),
                'published_by' => $admin->getKey(),
                'published_at' => now(),
            ]);
        }
    }

    private function clearExistingRewardData(): void
    {
        DB::table('progress_snapshots')->delete();
        DB::table('celebrations')->delete();
        DB::table('skill_progress_records')->delete();
        DB::table('curriculum_progress_records')->delete();
        DB::table('streak_records')->delete();
        DB::table('badge_awards')->delete();
        DB::table('reward_ledger_entries')->delete();
        DB::table('learner_progress_profiles')->delete();
        DB::table('progress_events')->delete();
        DB::table('reward_rules')->delete();
        DB::table('badge_definitions')->delete();
        DB::table('learner_levels')->delete();
    }
}
