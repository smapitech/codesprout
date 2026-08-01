<?php

namespace Tests\Feature\Assignments;

use App\Enums\AssignmentFeedbackType;
use App\Enums\AssignmentType;
use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Enums\RoleName;
use App\Models\AcademicCohort;
use App\Models\AssessmentRubricCriterion;
use App\Models\Assignment;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AssignmentRubricScore;
use App\Models\AuditLog;
use App\Models\LearningClass;
use App\Models\User;
use App\Services\Assignments\AssignmentAllocationService;
use App\Services\Assignments\AssignmentAttemptService;
use App\Services\Assignments\AssignmentQuestionHandlerRegistry;
use App\Services\Assignments\AssignmentReportService;
use App\Services\Assignments\AssignmentVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_and_teachers_can_open_assignment_management(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/assignments')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('assignments/index')
                ->has('assignments')
                ->where('role', 'administrator'));

        $this->actingAs($this->teacher())
            ->get('/teacher/assignments')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('assignments/index')
                ->has('markingQueue')
                ->where('role', 'teacher'));
    }

    public function test_teacher_create_private_drafts_and_other_teachers_cannot_open_them(): void
    {
        $teacher = $this->teacher();
        $otherTeacher = User::factory()->create(['email' => 'other.teacher@childsbridge.test']);
        $otherTeacher->assignRole(RoleName::Teacher->value);

        $this->actingAs($teacher)->post('/teacher/assignments', $this->validAssignmentPayload([
            'title' => 'Private Draft Mission',
        ]))->assertRedirect();

        $assignment = Assignment::query()->whereHas('currentVersion', fn ($query) => $query->where('title', 'Private Draft Mission'))->firstOrFail();

        $this->actingAs($otherTeacher)
            ->get(route('teacher.assignments.show', $assignment, absolute: false))
            ->assertForbidden();
    }

    public function test_teacher_allocation_is_limited_to_assigned_classes_and_children(): void
    {
        $teacher = $this->teacher();
        $version = $this->publishedVersion();
        $class = LearningClass::query()->where('class_code', 'CB-KEY-01')->firstOrFail();
        $unrelatedChild = User::factory()->create(['email' => null]);
        $unrelatedChild->assignRole(RoleName::Child->value);

        $allocation = app(AssignmentAllocationService::class)->createAllocation($version, [
            'class_id' => $class->id,
            'available_from' => now()->subHour(),
        ], $teacher);

        $this->assertDatabaseHas('assignment_allocations', ['id' => $allocation->id, 'class_id' => $class->id]);

        $this->expectException(HttpException::class);
        app(AssignmentAllocationService::class)->createAllocation($version, [
            'child_id' => $unrelatedChild->id,
            'available_from' => now()->subHour(),
        ], $teacher);
    }

    public function test_parents_cannot_create_or_submit_assignments(): void
    {
        $parent = $this->parentUser();
        $attempt = AssignmentAttempt::query()->firstOrFail();

        $this->actingAs($parent)->post('/teacher/assignments', $this->validAssignmentPayload())->assertForbidden();
        $this->actingAs($parent)->post(route('child.missions.attempts.submit', $attempt, absolute: false))->assertForbidden();
    }

    public function test_children_see_only_their_allocations_and_cannot_open_another_child_attempt(): void
    {
        $child = $this->childUser();
        $otherChild = User::query()
            ->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1002'))
            ->firstOrFail();
        $attempt = AssignmentAttempt::query()->where('child_id', $child->id)->firstOrFail();

        $this->actingAs($child)
            ->get('/child/missions')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('child/missions/index')
                ->has('missions.today'));

        $this->actingAs($otherChild)
            ->get(route('child.missions.attempts.show', $attempt, absolute: false))
            ->assertForbidden();
    }

    public function test_drafts_cannot_be_allocated_and_invalid_versions_cannot_publish(): void
    {
        $teacher = $this->teacher();
        $draft = app(AssignmentVersionService::class)->createDraft([
            'assignment_type' => AssignmentType::Mission->value,
            'title' => 'Incomplete Draft',
            'child_instructions' => '',
            'items' => [],
        ], $teacher);

        $this->expectException(ValidationException::class);
        app(AssignmentAllocationService::class)->createAllocation($draft, [
            'class_id' => LearningClass::query()->firstOrFail()->id,
        ], $teacher);
    }

    public function test_published_versions_are_not_edited_in_place_and_allocations_keep_original_version(): void
    {
        $teacher = $this->teacher();
        $assignment = Assignment::query()->where('status', ContentStatus::Published)->firstOrFail();
        $originalVersion = $assignment->currentVersion;
        $allocation = AssignmentAllocation::query()->where('assignment_version_id', $originalVersion->id)->firstOrFail();

        app(AssignmentVersionService::class)->saveDraft($assignment, $this->validAssignmentPayload([
            'title' => 'Fresh Draft After Publish',
        ]), $teacher);

        $assignment->refresh();

        $this->assertNotSame($originalVersion->id, $assignment->current_version_id);
        $this->assertDatabaseHas('assignment_allocations', [
            'id' => $allocation->id,
            'assignment_version_id' => $originalVersion->id,
        ]);
        $this->assertSame(ContentStatus::Published, $originalVersion->fresh()->status);
    }

    public function test_child_payload_does_not_expose_correct_answer_metadata(): void
    {
        $child = $this->childUser();
        $allocation = AssignmentAllocation::query()->with('assignmentVersion.items.options')->firstOrFail();

        $this->actingAs($child)
            ->get(route('child.missions.show', $allocation, absolute: false))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $items = $page->toArray()['props']['mission']['items'];
                $firstOption = $items[0]['options'][0] ?? [];

                $this->assertArrayNotHasKey('is_correct', $firstOption);
                $this->assertArrayNotHasKey('matching_key', $firstOption);
                $this->assertArrayNotHasKey('grading_configuration', $items[0]);
                $this->assertArrayNotHasKey('configuration', $items[0]);
                $this->assertNull($page->toArray()['props']['mission']['teacher_instructions']);
            });
    }

    public function test_automatic_grading_handles_choice_typing_symbols_matching_and_ordering(): void
    {
        $registry = app(AssignmentQuestionHandlerRegistry::class);

        $choice = AssignmentItem::query()->where('question_type', QuestionType::MultipleChoice)->firstOrFail();
        $this->assertSame(3, $registry->gradeResponse($choice, ['selected_option_value' => 'click'])['score']);

        $typing = AssignmentItem::query()->where('question_type', QuestionType::TypeWord)->firstOrFail();
        $this->assertSame(3, $registry->gradeResponse($typing, ['text' => 'Ada'])['score']);
        $this->assertSame(0, $registry->gradeResponse($typing, ['text' => 'ada'])['score']);

        $html = AssignmentItem::query()->where('question_type', QuestionType::BuildHtmlTag)->firstOrFail();
        $this->assertSame(3, $registry->gradeResponse($html, ['text' => '<h1>'])['score']);
        $this->assertSame(0, $registry->gradeResponse($html, ['text' => 'h1'])['score']);

        $matching = AssignmentItem::query()->where('question_type', QuestionType::MatchItems)->firstOrFail();
        $this->assertSame(3, $registry->gradeResponse($matching, ['pairs' => ['mouse' => 'Click and point', 'keyboard' => 'Type letters']])['score']);

        $ordering = AssignmentItem::query()->where('question_type', QuestionType::ArrangeCodeIntoCorrectOrder)->firstOrFail();
        $this->assertSame(3, $registry->gradeResponse($ordering, ['order' => ['<h1>', 'Hello', '</h1>']])['score']);
    }

    public function test_attempt_limits_closing_dates_autosave_resume_and_duplicate_submit_are_enforced(): void
    {
        $teacher = $this->teacher();
        $child = $this->childUser();
        $version = $this->publishedVersion();
        $class = LearningClass::query()->where('class_code', 'CB-KEY-01')->firstOrFail();
        $allocation = app(AssignmentAllocationService::class)->createAllocation($version, [
            'class_id' => $class->id,
            'available_from' => now()->subHour(),
            'closes_at' => now()->addHour(),
            'attempt_limit' => 1,
        ], $teacher);

        $attemptService = app(AssignmentAttemptService::class);
        $attempt = $attemptService->startAttempt($allocation, $child);
        $sameAttempt = $attemptService->startAttempt($allocation, $child);

        $this->assertSame($attempt->id, $sameAttempt->id);

        $item = $version->items->first();
        $attemptService->saveResponse($attempt, $item, ['selected_option_value' => 'click'], $child);

        $this->assertDatabaseHas('assignment_responses', [
            'assignment_attempt_id' => $attempt->id,
            'assignment_item_id' => $item->id,
            'text_response' => '',
        ]);

        $submitted = $attemptService->submitAttempt($attempt->fresh(), $child);
        $duplicate = $attemptService->submitAttempt($submitted->fresh(), $child);

        $this->assertSame($submitted->id, $duplicate->id);
        $this->assertDatabaseCount('assignment_attempts', AssignmentAttempt::query()->count());

        $closed = app(AssignmentAllocationService::class)->createAllocation($version, [
            'class_id' => $class->id,
            'available_from' => now()->subDays(2),
            'closes_at' => now()->subDay(),
            'attempt_limit' => 1,
        ], $teacher);

        $this->expectException(ValidationException::class);
        $attemptService->startAttempt($closed, $child);
    }

    public function test_manual_marking_caps_scores_returns_work_and_parent_feedback_is_scoped(): void
    {
        $teacher = $this->teacher();
        $parent = $this->parentUser();
        $attempt = AssignmentAttempt::query()
            ->whereHas('responses.item', fn ($query) => $query->where('question_type', QuestionType::CreativeProject->value))
            ->firstOrFail();
        $response = $attempt->responses()->firstOrFail();

        app(AssignmentAttemptService::class)->markAttempt($attempt, $teacher, [
            'manual_scores' => [$response->assignment_item_id => 999],
            'feedback_text' => 'Try adding one more colour.',
            'feedback_type' => AssignmentFeedbackType::RetryGuidance->value,
            'returned_for_retry' => true,
            'visible_to_child' => true,
            'visible_to_parent' => true,
        ]);

        $attempt->refresh();
        $this->assertLessThanOrEqual($attempt->maximum_score, (float) $attempt->final_score);
        $this->assertSame('returned', $attempt->status->value);

        $criterion = AssessmentRubricCriterion::query()->firstOrFail();
        app(AssignmentAttemptService::class)->markAttempt($attempt->fresh(), $teacher, [
            'rubric_scores' => [$criterion->id => $criterion->maximum_points + 50],
        ]);

        $rubricScore = AssignmentRubricScore::query()
            ->where('assignment_attempt_id', $attempt->id)
            ->where('rubric_criterion_id', $criterion->id)
            ->firstOrFail();

        $this->assertSame((float) $criterion->maximum_points, (float) $rubricScore->awarded_points);

        $assignments = app(AssignmentReportService::class)->parentAssignments($parent);
        $this->assertTrue($assignments->contains(fn ($item): bool => $item['teacher_feedback'] === 'Try adding one more colour.'));

        $unlinkedParent = User::factory()->create(['email' => 'unlinked.parent@childsbridge.test']);
        $unlinkedParent->assignRole(RoleName::Parent->value);
        $this->assertCount(0, app(AssignmentReportService::class)->parentAssignments($unlinkedParent));
    }

    public function test_teacher_reports_respect_class_boundaries_and_executable_uploads_are_rejected(): void
    {
        Storage::fake('private');
        $teacher = $this->teacher();
        $otherTeacher = User::factory()->create(['email' => 'external.teacher@childsbridge.test']);
        $otherTeacher->assignRole(RoleName::Teacher->value);
        $cohort = AcademicCohort::query()->firstOrFail();
        $otherClass = LearningClass::query()->create([
            'academic_cohort_id' => $cohort->id,
            'class_code' => 'OTHER-01',
            'name' => 'Other Class',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $summary = app(AssignmentReportService::class)->teacherSummary($teacher);
        $otherSummary = app(AssignmentReportService::class)->teacherSummary($otherTeacher);

        $this->assertGreaterThan(0, $summary['attempt_count']);
        $this->assertSame(0, $otherSummary['attempt_count']);
        $this->assertDatabaseHas('classes', ['id' => $otherClass->id]);

        $attempt = AssignmentAttempt::query()->firstOrFail();
        $item = $attempt->version->items()->firstOrFail();
        $file = UploadedFile::fake()->create('bad.php', 4, 'application/x-php');

        $this->actingAs($this->childUser())
            ->post(route('child.missions.attempts.attachments.store', [$attempt, $item], absolute: false), [
                'attachment' => $file,
            ])
            ->assertSessionHasErrors('attachment');
    }

    public function test_assignment_audit_records_are_created(): void
    {
        $this->assertTrue(AuditLog::query()->where('action', 'assignment.published')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'assignment.allocation.created')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'assignment.started')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'assignment.submitted')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'assignment.marked')->exists());
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

    private function childUser(): User
    {
        return User::query()
            ->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))
            ->firstOrFail();
    }

    private function publishedVersion()
    {
        return Assignment::query()
            ->whereHas('currentVersion', fn ($query) => $query->where('title', 'Mouse Click Choices'))
            ->firstOrFail()
            ->currentVersion()
            ->with('items.options')
            ->firstOrFail();
    }

    private function validAssignmentPayload(array $overrides = []): array
    {
        return array_merge([
            'assignment_type' => AssignmentType::Mission->value,
            'title' => 'Test Mission',
            'short_description' => 'A small assignment for tests.',
            'child_instructions' => 'Choose the answer.',
            'teacher_instructions' => 'Observe and support.',
            'estimated_minutes' => 5,
            'difficulty_level' => 'introductory',
            'default_attempt_limit' => 2,
            'feedback_mode' => 'after_submission',
            'scoring_method' => 'latest_attempt',
            'items' => [
                [
                    'title' => 'Choose click',
                    'prompt_text' => 'What opens a button?',
                    'question_type' => QuestionType::MultipleChoice->value,
                    'interaction_type' => QuestionType::MultipleChoice->interactionType()->value,
                    'points' => 2,
                    'is_required' => true,
                    'display_order' => 1,
                    'configuration' => [],
                    'grading_configuration' => [],
                    'options' => [
                        ['option_text' => 'Click', 'option_value' => 'click', 'is_correct' => true, 'display_order' => 1],
                        ['option_text' => 'Sleep', 'option_value' => 'sleep', 'is_correct' => false, 'display_order' => 2],
                    ],
                ],
            ],
            'curriculum_links' => [],
            'skill_ids' => [],
        ], $overrides);
    }
}
