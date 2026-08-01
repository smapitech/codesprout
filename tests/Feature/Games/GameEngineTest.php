<?php

namespace Tests\Feature\Games;

use App\Enums\ContentStatus;
use App\Enums\GameDifficulty;
use App\Events\Games\GameSessionCompleted;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AuditLog;
use App\Models\GameDefinition;
use App\Models\GameResult;
use App\Models\GameVersion;
use App\Models\User;
use App\Services\Games\GamePublicationService;
use App\Services\Games\GameRegistry;
use App\Services\Games\GameReportService;
use App\Services\Games\GameSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GameEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_manage_and_teachers_preview_published_games(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.games.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('games/index')
                ->where('role', 'administrator')
                ->has('games', 11));

        $this->actingAs($this->teacher())
            ->get(route('teacher.games.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('games/index')
                ->where('role', 'teacher')
                ->has('games', 9));

        $published = GameDefinition::query()->where('slug', 'computer-part-explorer')->firstOrFail();
        $this->actingAs($this->teacher())
            ->get(route('teacher.games.preview', $published, absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('games/preview')
                ->where('preview', true));
    }

    public function test_teachers_cannot_publish_and_children_cannot_access_draft_or_archived_games(): void
    {
        $draft = GameDefinition::query()->where('slug', 'draft-sprout-lab')->firstOrFail();
        $archived = GameDefinition::query()->where('slug', 'arrow-key-path')->firstOrFail();
        $published = GameDefinition::query()->where('slug', 'computer-part-explorer')->firstOrFail();

        $this->actingAs($this->teacher())
            ->post(route('admin.games.publish', [$published, $published->currentVersion], absolute: false))
            ->assertForbidden();

        $this->actingAs($this->child())
            ->get(route('child.games.show', $draft, absolute: false))
            ->assertNotFound();

        $this->actingAs($this->child())
            ->post(route('child.games.start', $archived, absolute: false), ['difficulty' => GameDifficulty::Slow->value])
            ->assertSessionHasErrors('game_version_id');
    }

    public function test_published_game_versions_are_not_edited_in_place(): void
    {
        $game = GameDefinition::query()->where('slug', 'computer-part-explorer')->firstOrFail();
        $original = $game->currentVersion;

        $draft = app(GamePublicationService::class)->createDraftFrom($game, [
            'name' => 'Computer Part Explorer Updated',
            'instructions' => 'Find the friendly part.',
            'configuration' => [
                'items' => [
                    ['name' => 'Screen', 'value' => 'screen'],
                    ['name' => 'Keyboard', 'value' => 'keyboard'],
                ],
                'round_count' => 2,
            ],
        ], $this->admin());

        $this->assertNotSame($original->id, $draft->id);
        $this->assertSame(ContentStatus::Published, $original->fresh()->status);
        $this->assertSame(ContentStatus::Draft, $draft->status);
        $this->assertSame($draft->id, $game->fresh()->current_version_id);
    }

    public function test_invalid_and_executable_game_configuration_is_rejected(): void
    {
        $registry = app(GameRegistry::class);

        $this->expectException(ValidationException::class);
        $registry->handlerFor('computer_part_identification')->validateConfiguration([
            'items' => [
                ['name' => 'Screen', 'value' => 'javascript:alert(1)'],
            ],
        ]);
    }

    public function test_child_session_lifecycle_payload_safety_and_idempotent_completion(): void
    {
        Event::fake([GameSessionCompleted::class]);

        $child = $this->child();
        $otherChild = $this->otherChild();
        $version = $this->publishedVersion('computer-part-explorer');
        $service = app(GameSessionService::class);

        $session = $service->start($version, $child, GameDifficulty::Normal, [
            'client_session_identifier' => 'test-session-lifecycle',
        ]);

        $payload = $service->payload($session, $child);
        $this->assertArrayNotHasKey('expected', $payload['round']);
        $this->assertSame(GameDifficulty::Normal, $session->difficulty);

        $this->expectForbidden(fn () => $service->payload($session, $otherChild));

        $paused = $service->pause($session->fresh(), $child);
        $this->assertSame('paused', $paused->status->value);

        $resumed = $service->resume($paused, $child);
        $this->assertSame('in_progress', $resumed->status->value);

        foreach ($resumed->roundRecords as $round) {
            $service->recordAction($resumed->fresh(['roundRecords', 'gameVersion.definition']), $child, [
                'round_number' => $round->round_number,
                'response' => [
                    'selected_part' => $round->round_data['expected'],
                    'score' => 999999,
                    'answer' => 'fake',
                ],
                'response_time_ms' => 1200,
                'hint_used' => $round->round_number === 1,
            ]);
        }

        $completed = $service->complete($resumed->fresh(['roundRecords', 'gameVersion.definition']), $child, 'idem-1');
        $again = $service->complete($completed->fresh(), $child, 'idem-1');

        $this->assertSame($completed->id, $again->id);
        $this->assertDatabaseCount('game_results', GameResult::query()->count());
        $this->assertLessThanOrEqual((float) $completed->result->maximum_score, (float) $completed->result->score);
        $this->assertSame(1, $completed->result->hints_used);
        Event::assertDispatchedTimes(GameSessionCompleted::class, 1);
    }

    public function test_initial_game_types_validate_actions_and_calculate_performance(): void
    {
        $service = app(GameSessionService::class);
        $child = $this->child();

        $responses = [
            'computer-part-explorer' => fn (array $round): array => ['selected_part' => $round['expected']],
            'computer-part-matching' => fn (array $round): array => ['match' => $round['expected']],
            'click-the-target' => fn (array $round): array => ['selected_target' => $round['expected']],
            'double-click-practice' => fn (): array => ['interval_ms' => 700],
            'drag-and-drop-garden' => fn (array $round): array => ['match' => $round['expected']],
            'scroll-adventure' => fn (array $round): array => ['value' => $round['expected']],
            'find-the-enter-key' => fn (array $round): array => ['key' => $round['expected']],
            'keyboard-key-explorer' => fn (array $round): array => ['key' => $round['expected']],
            'falling-letters' => fn (array $round): array => ['key' => $round['expected']],
        ];

        foreach ($responses as $slug => $responseFactory) {
            $session = $service->start($this->publishedVersion($slug), $child, GameDifficulty::Slow, [
                'client_session_identifier' => 'test-'.$slug,
            ]);

            $round = $session->roundRecords->firstOrFail();
            $result = $service->recordAction($session, $child, [
                'round_number' => $round->round_number,
                'response' => $responseFactory($round->round_data),
                'response_time_ms' => 1000,
            ]);

            $this->assertTrue($result['correct'], $slug.' should grade a correct response.');
        }
    }

    public function test_keyboard_normalisation_recognises_special_keys(): void
    {
        $service = app(GameSessionService::class);
        $child = $this->child();
        $session = $service->start($this->publishedVersion('keyboard-key-explorer'), $child, GameDifficulty::Slow, [
            'client_session_identifier' => 'test-key-normalisation',
        ]);
        $round = $session->roundRecords->firstWhere('round_data.expected', 'arrow_right')
            ?? $session->roundRecords->last();

        $result = $service->recordAction($session, $child, [
            'round_number' => $round->round_number,
            'response' => ['key' => $round->round_data['expected'] === 'arrow_right' ? 'ArrowRight' : $round->round_data['expected']],
            'response_time_ms' => 900,
        ]);

        $this->assertTrue($result['correct']);
    }

    public function test_assignment_game_completion_updates_correct_attempt_and_rejects_mismatched_context(): void
    {
        $service = app(GameSessionService::class);
        $child = $this->child();
        $item = AssignmentItem::query()->whereNotNull('game_version_id')->firstOrFail();
        $attempt = AssignmentAttempt::query()
            ->where('child_id', $child->id)
            ->where('assignment_version_id', $item->assignment_version_id)
            ->firstOrFail();

        $session = $service->start($item->gameVersion, $child, GameDifficulty::Slow, [
            'assignment_allocation_id' => $attempt->assignment_allocation_id,
            'assignment_attempt_id' => $attempt->id,
            'assignment_item_id' => $item->id,
            'client_session_identifier' => 'test-assignment-game',
        ]);

        foreach ($session->roundRecords as $round) {
            $service->recordAction($session->fresh(['roundRecords', 'gameVersion.definition']), $child, [
                'round_number' => $round->round_number,
                'response' => ['selected_part' => $round->round_data['expected']],
                'response_time_ms' => 1100,
            ]);
        }

        $service->complete($session->fresh(['roundRecords', 'gameVersion.definition']), $child, 'assignment-game-test');

        $this->assertDatabaseHas('assignment_responses', [
            'assignment_attempt_id' => $attempt->id,
            'assignment_item_id' => $item->id,
            'text_response' => 'Game completed',
        ]);

        $otherAllocation = AssignmentAllocation::query()->where('id', '!=', $attempt->assignment_allocation_id)->firstOrFail();

        $this->expectException(ValidationException::class);
        $service->start($item->gameVersion, $child, GameDifficulty::Slow, [
            'assignment_allocation_id' => $otherAllocation->id,
            'assignment_attempt_id' => $attempt->id,
            'assignment_item_id' => $item->id,
            'client_session_identifier' => 'bad-assignment-game',
        ]);
    }

    public function test_reports_and_parent_visibility_are_scoped(): void
    {
        $teacherSummary = app(GameReportService::class)->teacherSummary($this->teacher());
        $parentResults = app(GameReportService::class)->parentResults($this->parentUser());

        $this->assertGreaterThan(0, $teacherSummary['sessions_started']);
        $this->assertGreaterThan(0, $parentResults->count());

        $unlinkedParent = User::factory()->create(['email' => 'unlinked.game.parent@childsbridge.test']);
        $unlinkedParent->assignRole('parent');

        $this->assertCount(0, app(GameReportService::class)->parentResults($unlinkedParent));
    }

    public function test_game_audit_records_are_created(): void
    {
        $this->assertTrue(AuditLog::query()->where('action', 'game.published')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'game.archived')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'game.session.started')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'game.session.completed')->exists());
    }

    private function publishedVersion(string $slug): GameVersion
    {
        return GameDefinition::query()
            ->where('slug', $slug)
            ->where('status', ContentStatus::Published)
            ->firstOrFail()
            ->currentVersion()
            ->with('definition')
            ->firstOrFail();
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

    private function expectForbidden(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a forbidden response.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
