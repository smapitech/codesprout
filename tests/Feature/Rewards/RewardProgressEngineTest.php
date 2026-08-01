<?php

namespace Tests\Feature\Rewards;

use App\Enums\ContentStatus;
use App\Enums\RewardRepeatPolicy;
use App\Enums\RewardType;
use App\Models\BadgeAward;
use App\Models\GameSession;
use App\Models\LearnerProgressProfile;
use App\Models\ProgressEvent;
use App\Models\RewardLedgerEntry;
use App\Models\RewardRule;
use App\Models\User;
use App\Services\Rewards\ProgressEventProcessor;
use App\Services\Rewards\ProgressRecalculationService;
use App\Services\Rewards\ProgressReportService;
use App\Services\Rewards\RewardAwardService;
use App\Services\Rewards\RewardRulePublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RewardProgressEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_scoped_progress_pages_are_available_to_the_right_roles(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.rewards.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/rewards/index')
                ->has('rules.data')
                ->has('badges', 21)
                ->has('levels', 7));

        $this->actingAs($this->teacher())
            ->get(route('teacher.progress.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('teacher/progress/index')
                ->has('learners', 2));

        $this->actingAs($this->parentUser())
            ->get(route('parent.progress.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('parent/progress/index')
                ->has('children', 2));

        $this->actingAs($this->child())
            ->get(route('child.rewards.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('child/rewards/index')
                ->where('child.name', 'Amara Stone')
                ->has('profile')
                ->has('recent_badges'));

        $this->actingAs($this->parentUser())
            ->get(route('admin.rewards.index', absolute: false))
            ->assertForbidden();
    }

    public function test_published_reward_rules_are_versioned_instead_of_edited_in_place(): void
    {
        $rule = RewardRule::query()->where('slug', 'mission-completion-stars')->firstOrFail();

        $replacement = app(RewardRulePublicationService::class)->updateOrVersion($rule, [
            'name' => 'Mission completion stars updated',
            'slug' => 'mission-completion-stars',
            'event_type' => 'assignment.completed',
            'source_type' => null,
            'eligibility_conditions' => [],
            'reward_type' => RewardType::Stars,
            'reward_amount' => 20,
            'repeat_policy' => RewardRepeatPolicy::OncePerSource,
            'priority' => 90,
        ], $this->admin());

        $this->assertNotSame($rule->id, $replacement->id);
        $this->assertSame(ContentStatus::Published, $rule->fresh()->status);
        $this->assertSame(ContentStatus::Draft, $replacement->status);
        $this->assertSame(2, $replacement->version);
        $this->assertSame(15, $rule->fresh()->reward_amount);
    }

    public function test_unsafe_reward_conditions_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(RewardRulePublicationService::class)->validate([
            'event_type' => 'game.completed',
            'reward_type' => RewardType::Stars,
            'reward_amount' => 10,
            'repeat_policy' => RewardRepeatPolicy::OncePerSource,
            'eligibility_conditions' => [
                'raw_sql' => 'select * from users',
                'minimum_accuracy' => '<script>alert(1)</script>',
            ],
        ]);
    }

    public function test_duplicate_events_do_not_duplicate_stars_xp_badges_streaks_or_celebrations(): void
    {
        $child = $this->child();
        $session = GameSession::query()->where('child_id', $child->id)->whereNotNull('completed_at')->firstOrFail();
        $processor = app(ProgressEventProcessor::class);

        $before = [
            'ledger' => RewardLedgerEntry::query()->where('child_id', $child->id)->count(),
            'badges' => BadgeAward::query()->where('child_id', $child->id)->count(),
            'events' => ProgressEvent::query()->where('child_id', $child->id)->count(),
            'profile' => LearnerProgressProfile::query()->where('child_id', $child->id)->firstOrFail()->only(['total_stars', 'total_experience', 'current_streak']),
        ];

        $processor->fromGameCompleted($session);
        $processor->fromGameCompleted($session);

        $profile = LearnerProgressProfile::query()->where('child_id', $child->id)->firstOrFail();
        $this->assertSame($before['ledger'], RewardLedgerEntry::query()->where('child_id', $child->id)->count());
        $this->assertSame($before['badges'], BadgeAward::query()->where('child_id', $child->id)->count());
        $this->assertSame($before['events'], ProgressEvent::query()->where('child_id', $child->id)->count());
        $this->assertSame($before['profile']['total_stars'], $profile->total_stars);
        $this->assertSame($before['profile']['total_experience'], $profile->total_experience);
        $this->assertSame($before['profile']['current_streak'], $profile->current_streak);
    }

    public function test_client_submitted_reward_totals_are_ignored(): void
    {
        $child = $this->child();
        $before = LearnerProgressProfile::query()->where('child_id', $child->id)->firstOrFail()->total_stars;

        app(ProgressEventProcessor::class)->process([
            'event_type' => 'game.completed',
            'child_id' => $child->id,
            'source_type' => 'client_fake',
            'source_id' => 999999,
            'performance_summary' => ['accuracy' => 100, 'score' => 999999],
            'metadata' => ['client_stars' => 999999, 'client_xp' => 999999],
            'idempotency_key' => 'test-client-fake-score',
        ]);

        $profile = LearnerProgressProfile::query()->where('child_id', $child->id)->firstOrFail();
        $this->assertLessThan(999999, $profile->total_stars);
        $this->assertSame($before + 10, $profile->total_stars);
    }

    public function test_archived_rules_do_not_create_new_rewards(): void
    {
        RewardRule::query()->where('event_type', 'game.completed')->update(['status' => ContentStatus::Archived]);
        $child = $this->otherChild();

        app(ProgressEventProcessor::class)->process([
            'event_type' => 'game.completed',
            'child_id' => $child->id,
            'source_type' => 'manual_test',
            'source_id' => 12345,
            'performance_summary' => ['accuracy' => 100],
            'idempotency_key' => 'archived-rules-do-not-award',
        ]);

        $this->assertDatabaseMissing('reward_ledger_entries', [
            'child_id' => $child->id,
            'source_type' => 'manual_test',
            'source_id' => 12345,
        ]);
    }

    public function test_levels_badges_and_streaks_are_projected_from_validated_events(): void
    {
        $child = $this->child();
        $profile = LearnerProgressProfile::query()->with('currentLevel')->where('child_id', $child->id)->firstOrFail();

        $this->assertGreaterThan(0, $profile->total_stars);
        $this->assertGreaterThan(0, $profile->total_experience);
        $this->assertNotNull($profile->currentLevel);
        $this->assertGreaterThanOrEqual(1, $profile->current_streak);
        $this->assertDatabaseHas('badge_awards', ['child_id' => $child->id]);
    }

    public function test_parent_and_teacher_reports_respect_relationship_boundaries(): void
    {
        $teacherSummary = app(ProgressReportService::class)->teacherClassSummary($this->teacher());
        $parentSummary = app(ProgressReportService::class)->parentSummary($this->parentUser());

        $this->assertCount(2, $teacherSummary['learners']);
        $this->assertCount(2, $parentSummary['children']);

        $unlinkedParent = User::factory()->create(['email' => 'unlinked.progress.parent@childsbridge.test']);
        $unlinkedParent->assignRole('parent');

        $this->assertCount(0, app(ProgressReportService::class)->parentSummary($unlinkedParent)['children']);
    }

    public function test_recalculation_dry_run_does_not_change_profile_and_actual_rebuilds_totals(): void
    {
        $child = $this->child();
        $profile = LearnerProgressProfile::query()->where('child_id', $child->id)->firstOrFail();
        $profile->forceFill(['total_stars' => 1, 'total_experience' => 1])->save();

        $dryRun = app(ProgressRecalculationService::class)->recalculate($child, true);
        $this->assertTrue($dryRun['dry_run']);
        $this->assertSame(1, $profile->fresh()->total_stars);

        $actual = app(ProgressRecalculationService::class)->recalculate($child, false);
        $this->assertFalse($actual['dry_run']);
        $this->assertSame($actual['calculated']['stars'], $profile->fresh()->total_stars);
    }

    public function test_authorised_adjustments_require_reason_and_cannot_make_totals_negative(): void
    {
        $this->expectException(ValidationException::class);
        app(RewardAwardService::class)->adjust($this->child(), -999999, RewardType::Stars, $this->admin(), 'Too much');
    }

    private function admin(): User
    {
        return User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
    }

    private function teacher(): User
    {
        return User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
    }

    private function parentUser(): User
    {
        return User::query()->where('email', 'parent@childsbridge.test')->firstOrFail();
    }

    private function child(): User
    {
        return User::query()
            ->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))
            ->firstOrFail();
    }

    private function otherChild(): User
    {
        return User::query()
            ->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1002'))
            ->firstOrFail();
    }
}
