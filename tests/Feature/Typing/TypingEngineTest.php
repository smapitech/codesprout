<?php

namespace Tests\Feature\Typing;

use App\Enums\ContentStatus;
use App\Enums\TypingExerciseType;
use App\Enums\TypingSessionStatus;
use App\Events\Typing\TypingSessionCompleted;
use App\Models\AuditLog;
use App\Models\ProgressEvent;
use App\Models\RewardLedgerEntry;
use App\Models\TypingExercise;
use App\Models\TypingExerciseVersion;
use App\Models\TypingResult;
use App\Models\User;
use App\Services\Typing\TypingExercisePublicationService;
use App\Services\Typing\TypingMetricCalculator;
use App\Services\Typing\TypingReportService;
use App\Services\Typing\TypingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TypingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_manage_typing_and_teachers_preview_published_exercises(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.typing.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('typing/admin/index')
                ->has('exercises', 20));

        $this->actingAs($this->teacher())
            ->get(route('teacher.typing.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('typing/teacher/index')
                ->has('exercises', 18));

        $published = TypingExercise::query()->where('slug', 'first-three-letter-words')->firstOrFail();
        $this->actingAs($this->teacher())
            ->get(route('teacher.typing.preview', $published, absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('typing/teacher/preview')
                ->where('banner', 'Teacher Preview - no learner progress or rewards will be created.'));
    }

    public function test_only_administrators_create_and_publish_typing_exercises(): void
    {
        $payload = $this->exercisePayload('Safe Letter Set');

        $this->actingAs($this->teacher())
            ->post(route('admin.typing.store', absolute: false), $payload)
            ->assertForbidden();

        $this->actingAs($this->parentUser())
            ->post(route('admin.typing.store', absolute: false), $payload)
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->post(route('admin.typing.store', absolute: false), $payload)
            ->assertRedirect();

        $exercise = TypingExercise::query()->where('slug', 'safe-letter-set')->firstOrFail();
        app(TypingExercisePublicationService::class)->publish($exercise->currentVersion, $this->admin());

        $this->assertSame(ContentStatus::Published, $exercise->fresh()->status);
    }

    public function test_published_exercises_are_versioned_instead_of_edited_in_place(): void
    {
        $exercise = TypingExercise::query()->where('slug', 'first-three-letter-words')->firstOrFail();
        $original = $exercise->currentVersion;
        $draft = app(TypingExercisePublicationService::class)->createDraftFrom($exercise, $this->exercisePayload('First Three Letter Words Updated'), $this->admin());

        $this->assertNotSame($original->id, $draft->id);
        $this->assertSame(ContentStatus::Published, $original->fresh()->status);
        $this->assertSame(ContentStatus::Draft, $draft->status);
        $this->assertSame($draft->id, $exercise->fresh()->current_version_id);
    }

    public function test_children_cannot_start_draft_or_archived_exercises(): void
    {
        $draft = TypingExercise::query()->where('slug', 'draft-home-row-garden')->firstOrFail();
        $archived = TypingExercise::query()->where('slug', 'archived-typing-path')->firstOrFail();

        $this->actingAs($this->child())
            ->post(route('child.typing.start', $draft, absolute: false))
            ->assertNotFound();

        $this->actingAs($this->child())
            ->post(route('child.typing.start', $archived, absolute: false))
            ->assertNotFound();
    }

    public function test_child_session_payload_does_not_expose_hidden_answer_sets(): void
    {
        $service = app(TypingSessionService::class);
        $session = $service->start($this->publishedVersion('first-three-letter-words'), $this->child(), [
            'client_session_identifier' => 'payload-safety',
        ]);

        $payload = $service->payload($session, $this->child());

        $this->assertArrayNotHasKey('expected_text', $payload['items'][0]);
        $this->assertArrayNotHasKey('normalised_expected_text', $payload['items'][0]);
        $this->expectForbidden(fn () => $service->payload($session, $this->otherChild()));
    }

    public function test_session_lifecycle_pause_resume_batch_and_idempotent_completion(): void
    {
        Event::fake([TypingSessionCompleted::class]);

        $service = app(TypingSessionService::class);
        $child = $this->child();
        $session = $service->start($this->publishedVersion('first-three-letter-words'), $child, [
            'client_session_identifier' => 'typing-lifecycle',
        ]);

        $paused = $service->pause($session, $child);
        $this->assertSame(TypingSessionStatus::Paused, $paused->status);

        $resumed = $service->resume($paused, $child);
        $this->assertSame(TypingSessionStatus::Resumed, $resumed->status);

        $batch = [
            'batch_uuid' => (string) Str::uuid(),
            'events' => $this->eventsFor("cat\nsun"),
        ];
        $service->recordBatch($resumed->fresh(), $child, $batch);
        $service->recordBatch($resumed->fresh(), $child, $batch);

        $completed = $service->complete($resumed->fresh(), $child, 'typing-lifecycle-complete');
        $again = $service->complete($completed->fresh(), $child, 'typing-lifecycle-complete');

        $this->assertSame($completed->id, $again->id);
        $this->assertSame(1, TypingResult::query()->where('typing_session_id', $completed->id)->count());
        Event::assertDispatchedTimes(TypingSessionCompleted::class, 1);
    }

    public function test_conflicting_duplicate_batches_are_rejected(): void
    {
        $service = app(TypingSessionService::class);
        $child = $this->child();
        $session = $service->start($this->publishedVersion('vowel-adventure'), $child, [
            'client_session_identifier' => 'batch-conflict',
        ]);
        $uuid = (string) Str::uuid();

        $service->recordBatch($session, $child, [
            'batch_uuid' => $uuid,
            'events' => $this->eventsFor('a'),
        ]);

        $this->expectException(ValidationException::class);
        $service->recordBatch($session->fresh(), $child, [
            'batch_uuid' => $uuid,
            'events' => $this->eventsFor('e'),
        ]);
    }

    public function test_accuracy_speed_and_error_classification_are_server_calculated(): void
    {
        $service = app(TypingSessionService::class);
        $child = $this->child();
        $session = $service->start($this->publishedVersion('short-sentence-practice'), $child, [
            'client_session_identifier' => 'metric-test',
        ]);

        $events = [[
            'sequence_number' => 1,
            'character_position' => 0,
            'event_type' => 'input',
            'expected_character' => 'I',
            'entered_character' => 'i',
            'correctness_state' => 'incorrect',
            'elapsed_offset_ms' => 20000,
        ], [
            'sequence_number' => 2,
            'character_position' => 0,
            'event_type' => 'backspace',
            'correctness_state' => 'corrected',
            'elapsed_offset_ms' => 21200,
        ]];

        foreach (preg_split('//u', 'I can type.', -1, PREG_SPLIT_NO_EMPTY) ?: [] as $position => $char) {
            $events[] = [
                'sequence_number' => $position + 3,
                'character_position' => $position,
                'event_type' => 'input',
                'expected_character' => mb_substr('I can type.', $position, 1),
                'entered_character' => $char,
                'correctness_state' => 'correct',
                'elapsed_offset_ms' => 22400 + ($position * 1200),
            ];
        }

        $service->recordBatch($session, $child, [
            'batch_uuid' => (string) Str::uuid(),
            'events' => $events,
        ]);

        $result = app(TypingMetricCalculator::class)->calculate($session->fresh(['inputEvents', 'exerciseVersion.contentItems']));

        $this->assertLessThan(100, $result['first_attempt_accuracy']);
        $this->assertSame(100.0, $result['final_text_accuracy']);
        $this->assertSame(1, $result['corrected_errors']);
        $this->assertNotNull($result['gross_words_per_minute']);
    }

    public function test_paste_events_need_review_and_do_not_create_completion_rewards(): void
    {
        $service = app(TypingSessionService::class);
        $child = $this->child();
        $before = RewardLedgerEntry::query()->where('child_id', $child->id)->count();
        $session = $service->start($this->publishedVersion('accuracy-builder'), $child, [
            'client_session_identifier' => 'paste-review',
        ]);

        $service->recordBatch($session, $child, [
            'batch_uuid' => (string) Str::uuid(),
            'events' => [[
                'sequence_number' => 1,
                'character_position' => 0,
                'event_type' => 'paste',
                'expected_character' => 'r',
                'entered_character' => 'red cat',
                'correctness_state' => 'assistance',
                'elapsed_offset_ms' => 30000,
            ]],
        ]);
        $completed = $service->complete($session->fresh(), $child, 'paste-review');

        $this->assertSame('needs_review', $completed->result->validity_status->value);
        $this->assertSame($before, RewardLedgerEntry::query()->where('child_id', $child->id)->count());
    }

    public function test_valid_typing_completion_dispatches_progress_event_and_rewards_once(): void
    {
        $service = app(TypingSessionService::class);
        $child = $this->child();
        $session = $service->start($this->publishedVersion('first-three-letter-words'), $child, [
            'client_session_identifier' => 'reward-test',
        ]);
        $before = RewardLedgerEntry::query()->where('child_id', $child->id)->count();

        $service->recordBatch($session, $child, [
            'batch_uuid' => (string) Str::uuid(),
            'events' => $this->eventsFor("cat\nsun"),
        ]);
        $service->complete($session->fresh(), $child, 'reward-test');
        $service->complete($session->fresh(), $child, 'reward-test');

        $this->assertSame(1, ProgressEvent::query()->where('idempotency_key', 'typing.completed:'.$session->id)->count());
        $this->assertGreaterThan($before, RewardLedgerEntry::query()->where('child_id', $child->id)->count());
    }

    public function test_reports_respect_teacher_and_parent_boundaries(): void
    {
        $reports = app(TypingReportService::class);
        $this->assertGreaterThan(0, $reports->teacherRows($this->teacher())->count());
        $this->assertGreaterThan(0, $reports->parentRows($this->parentUser())->count());

        $unlinkedParent = User::factory()->create(['email' => 'unlinked.typing.parent@childsbridge.test']);
        $unlinkedParent->assignRole('parent');
        $this->assertCount(0, $reports->parentRows($unlinkedParent));

        $unassignedTeacher = User::factory()->create(['email' => 'unassigned.typing.teacher@childsbridge.test']);
        $unassignedTeacher->assignRole('teacher');
        $this->assertCount(0, $reports->teacherRows($unassignedTeacher));
    }

    public function test_unsafe_content_and_unsupported_keys_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(TypingExercisePublicationService::class)->createDraft($this->exercisePayload('Unsafe Typing', [
            ['prompt_text' => 'Type safe.', 'expected_text' => '<script>alert(1)</script>', 'target_keys' => ['not-a-real-key']],
        ]), $this->admin());
    }

    public function test_typing_audit_records_are_created(): void
    {
        $this->assertTrue(AuditLog::query()->where('action', 'typing.exercise.published')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'typing.exercise.archived')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'typing.session.started')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'typing.session.completed')->exists());
    }

    private function publishedVersion(string $slug): TypingExerciseVersion
    {
        return TypingExercise::query()
            ->where('slug', $slug)
            ->where('status', ContentStatus::Published)
            ->firstOrFail()
            ->currentVersion()
            ->with(['exercise', 'contentItems'])
            ->firstOrFail();
    }

    private function exercisePayload(string $title, ?array $items = null): array
    {
        return [
            'title' => $title,
            'exercise_type' => TypingExerciseType::WordTyping->value,
            'child_instructions' => 'Type each word carefully.',
            'content_configuration' => ['minimum_items' => 1, 'maximum_items' => 1],
            'completion_criteria' => ['minimum_items' => 1, 'minimum_accuracy' => 60, 'allow_pause' => true],
            'accuracy_requirement' => 60,
            'items' => $items ?? [
                ['prompt_text' => 'Type cat.', 'expected_text' => 'cat', 'target_keys' => ['c', 'a', 't']],
            ],
        ];
    }

    private function eventsFor(string $entered, ?string $expected = null): array
    {
        $expected ??= $entered;
        $events = [];
        foreach (preg_split('//u', $entered, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $position => $char) {
            $events[] = [
                'sequence_number' => $position + 1,
                'character_position' => $position,
                'event_type' => 'input',
                'expected_character' => mb_substr($expected, $position, 1),
                'entered_character' => $char,
                'correctness_state' => $char === mb_substr($expected, $position, 1) ? 'correct' : 'incorrect',
                'elapsed_offset_ms' => 20000 + ($position * 1200),
            ];
        }

        return $events;
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
        return User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))->firstOrFail();
    }

    private function otherChild(): User
    {
        return User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1002'))->firstOrFail();
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
