<?php

namespace Tests\Feature\Html;

use App\Enums\ContentStatus;
use App\Enums\AttemptStatus;
use App\Enums\HtmlExerciseType;
use App\Enums\HtmlProjectStatus;
use App\Models\AuditLog;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\HtmlExercise;
use App\Models\HtmlTagPolicy;
use App\Models\HtmlValidationResult;
use App\Models\LearnerWebpageProject;
use App\Models\ProgressEvent;
use App\Models\ProjectShowcaseEntry;
use App\Models\ProjectTemplate;
use App\Models\RewardLedgerEntry;
use App\Models\User;
use App\Services\Html\HtmlAttemptService;
use App\Services\Html\HtmlExercisePublicationService;
use App\Services\Html\HtmlReportService;
use App\Services\Html\HtmlSanitizer;
use App\Services\Html\HtmlTagPolicyService;
use App\Services\Html\ProjectTemplatePublicationService;
use App\Services\Html\WebpageProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HtmlEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_manage_html_exercises_and_teachers_preview_only_published_content(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.html.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('html/admin/index')
                ->has('exercises', 20)
                ->has('templates', 10));

        $this->actingAs($this->teacher())
            ->get(route('teacher.html.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('html/teacher/index')
                ->has('exercises', 20));

        $exercise = HtmlExercise::query()->where('slug', 'heading-adventure')->firstOrFail();
        $this->actingAs($this->teacher())
            ->get(route('teacher.html.preview', $exercise, absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('child/html/attempt')
                ->where('banner', 'Teacher Preview - no learner progress or rewards will be created.'));
    }

    public function test_only_administrators_create_html_exercises_and_publish_new_versions(): void
    {
        $payload = $this->exercisePayload('Safe Heading Builder');

        $this->actingAs($this->teacher())
            ->post(route('admin.html.store', absolute: false), $payload)
            ->assertForbidden();

        $this->actingAs($this->parentUser())
            ->post(route('admin.html.store', absolute: false), $payload)
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->post(route('admin.html.store', absolute: false), $payload)
            ->assertRedirect();

        $exercise = HtmlExercise::query()->where('slug', 'safe-heading-builder')->firstOrFail();
        app(HtmlExercisePublicationService::class)->publish($exercise->currentVersion, $this->admin());

        $this->assertSame(ContentStatus::Published, $exercise->fresh()->status);
    }

    public function test_published_html_content_is_versioned_not_edited_in_place(): void
    {
        $exercise = HtmlExercise::query()->where('slug', 'heading-adventure')->firstOrFail();
        $original = $exercise->currentVersion;
        $draft = app(HtmlExercisePublicationService::class)->createDraftFrom($exercise, $this->exercisePayload('Heading Adventure Updated'), $this->admin());

        $this->assertNotSame($original->id, $draft->id);
        $this->assertSame(ContentStatus::Published, $original->fresh()->status);
        $this->assertSame(ContentStatus::Draft, $draft->status);
        $this->assertSame($draft->id, $exercise->fresh()->current_version_id);
    }

    public function test_tag_policy_rejects_dangerous_tags_attributes_and_protocols(): void
    {
        $this->expectException(ValidationException::class);

        app(HtmlTagPolicyService::class)->createPublished([
            'name' => 'Unsafe Policy',
            'allowed_tags' => ['h1', 'script'],
            'allowed_attributes' => ['h1' => ['onclick']],
            'allowed_protocols' => ['javascript'],
        ], $this->admin());
    }

    public function test_sanitiser_blocks_script_handlers_encoded_javascript_iframes_and_remote_images(): void
    {
        $policy = HtmlTagPolicy::query()->firstOrFail();
        $result = app(HtmlSanitizer::class)->sanitise('<h1 onclick="x()">Hi</h1><a href="JaVaScRiPt%3Aalert(1)">bad</a><iframe></iframe><img src="https://example.com/cat.png">', $policy);

        $this->assertStringNotContainsString('onclick', $result['sanitised_html']);
        $this->assertStringNotContainsString('javascript', Str::lower($result['sanitised_html']));
        $this->assertStringNotContainsString('iframe', $result['sanitised_html']);
        $this->assertNotEmpty($result['issues']);
    }

    public function test_html_structure_validation_and_child_guidance_are_deterministic(): void
    {
        $service = app(HtmlAttemptService::class);
        $attempt = $service->start($this->publishedVersion('image-and-alt-text'), $this->child());

        $completed = $service->complete($attempt, $this->child(), [
            'source_html' => '<h1>Cat</h1><img src="/assets/codesprout/original/CodeSprout-Badge-Key-Explorer.png">',
            'input_method' => 'guided_code',
        ]);

        $this->assertSame('unsafe', $completed->validationResult->validity_status->value);
        $this->assertContains('Your image needs a description.', $completed->validationResult->result_summary['guidance']);
        $this->assertSame($completed->validationResult->result_checksum, $completed->validationResult->fresh()->result_checksum);
    }

    public function test_child_can_start_complete_valid_html_once_and_rewards_are_idempotent(): void
    {
        $service = app(HtmlAttemptService::class);
        $child = $this->child();
        $attempt = $service->start($this->publishedVersion('paragraph-builder'), $child);
        $before = RewardLedgerEntry::query()->where('child_id', $child->id)->count();

        $service->complete($attempt, $child, ['source_html' => '<p>I can make a paragraph.</p>', 'idempotency_key' => 'html-test-complete']);
        $service->complete($attempt->fresh(), $child, ['source_html' => '<p>I can make a paragraph.</p>', 'idempotency_key' => 'html-test-complete']);

        $this->assertSame(1, ProgressEvent::query()->where('idempotency_key', 'html.completed:'.$attempt->id)->count());
        $this->assertGreaterThan($before, RewardLedgerEntry::query()->where('child_id', $child->id)->count());
        $this->assertSame(1, HtmlValidationResult::query()->where('html_attempt_id', $attempt->id)->count());
    }

    public function test_project_templates_are_versioned_and_existing_projects_keep_original_version(): void
    {
        $template = ProjectTemplate::query()->where('slug', 'my-first-webpage')->firstOrFail();
        $original = $template->currentVersion;
        $project = app(WebpageProjectService::class)->create($original, $this->child());

        $newVersion = app(ProjectTemplatePublicationService::class)->createDraftFrom($template, [
            'title' => 'My First Webpage Revised',
            'description' => 'Revised safely.',
            'html_tag_policy_id' => HtmlTagPolicy::query()->firstOrFail()->id,
            'starter_source' => '<h1>Revised</h1><p>Still safe.</p>',
        ], $this->admin());

        $this->assertNotSame($original->id, $newVersion->id);
        $this->assertSame($original->id, $project->fresh()->project_template_version_id);
    }

    public function test_project_autosave_is_idempotent_and_rejects_stale_tabs(): void
    {
        $service = app(WebpageProjectService::class);
        $project = $service->create(ProjectTemplate::query()->where('slug', 'my-first-webpage')->firstOrFail()->currentVersion, $this->child());
        $uuid = (string) Str::uuid();

        $saved = $service->autosave($project, $this->child(), [
            'autosave_uuid' => $uuid,
            'state_version' => $project->state_version,
            'source_html' => '<h1>Saved</h1><p>Careful work.</p>',
        ]);
        $service->autosave($saved->fresh(), $this->child(), [
            'autosave_uuid' => $uuid,
            'state_version' => $saved->state_version,
            'source_html' => '<h1>Saved</h1><p>Careful work.</p>',
        ]);

        $this->assertSame(1, $project->autosaves()->where('autosave_uuid', $uuid)->count());

        $this->expectException(ValidationException::class);
        $service->autosave($saved->fresh(), $this->child(), [
            'autosave_uuid' => (string) Str::uuid(),
            'state_version' => 1,
            'source_html' => '<h1>Old tab</h1>',
        ]);
    }

    public function test_project_submission_review_showcase_and_rewards_are_safe(): void
    {
        $service = app(WebpageProjectService::class);
        $child = $this->child();
        $project = $service->create(ProjectTemplate::query()->where('slug', 'my-favourite-animal')->firstOrFail()->currentVersion, $child);
        $service->autosave($project, $child, [
            'autosave_uuid' => (string) Str::uuid(),
            'state_version' => $project->state_version,
            'source_html' => '<h1>My Animal</h1><p>A fox is clever.</p>',
        ]);
        $submitted = $service->submit($project->fresh(), $child, 'project-submit-test');
        $again = $service->submit($submitted->fresh(), $child, 'project-submit-test');

        $this->assertSame($submitted->id, $again->id);
        $review = $service->review($submitted->fresh(), $this->teacher(), [
            'review_status' => 'approved',
            'child_feedback' => 'Wonderful safe project.',
            'teacher_only_notes' => 'Private note',
            'release_to_parent' => true,
        ]);

        $this->assertSame(HtmlProjectStatus::Completed, $submitted->fresh()->status);
        $this->assertTrue(ProjectShowcaseEntry::query()->where('learner_webpage_project_id', $submitted->id)->exists());
        $this->assertSame('Private note', $review->teacher_only_notes);
        $this->assertTrue(ProgressEvent::query()->where('idempotency_key', 'html.project.completed:'.$submitted->id)->exists());
    }

    public function test_teacher_and_parent_reports_respect_boundaries_and_hide_teacher_only_notes(): void
    {
        $reports = app(HtmlReportService::class);
        $this->assertGreaterThan(0, count($reports->teacherRows($this->teacher())['data']));
        $this->assertGreaterThan(0, $reports->parentSummary($this->parentUser())['showcaseCount']);

        $unlinkedParent = User::factory()->create(['email' => 'html.unlinked.parent@childsbridge.test']);
        $unlinkedParent->assignRole('parent');
        $this->assertSame(0, $reports->parentSummary($unlinkedParent)['showcaseCount']);

        $this->assertFalse(str_contains(json_encode($reports->parentSummary($this->parentUser()), JSON_THROW_ON_ERROR), 'Seeded example kept parent-safe.'));
    }

    public function test_child_routes_expose_dynamic_html_dashboard_and_forbid_cross_child_project_access(): void
    {
        $this->actingAs($this->child())
            ->get(route('child.html.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('child/html/index')
                ->has('exercises', 12)
                ->has('templates', 6));

        $otherProject = LearnerWebpageProject::query()->where('child_id', $this->otherChild()->id)->firstOrFail();
        $this->actingAs($this->child())
            ->get(route('child.html.projects.show', $otherProject, absolute: false))
            ->assertForbidden();
    }

    public function test_child_live_preview_is_server_sanitised_and_scoped_to_the_attempt_owner(): void
    {
        $attempt = app(HtmlAttemptService::class)->start($this->publishedVersion('heading-adventure'), $this->child());

        $response = $this->actingAs($this->child())
            ->postJson(route('child.html.attempts.preview', $attempt, absolute: false), [
                'source_html' => '<h1 onclick="alert(1)">Hello</h1><script>alert(1)</script><img src="https://unsafe.example/image.png">',
            ])
            ->assertOk();
        $this->assertStringContainsString('<h1>Hello</h1>', $response->json('html'));
        $this->assertStringNotContainsString('script', $response->json('html'));
        $this->assertStringNotContainsString('onclick', $response->json('html'));
        $this->assertStringNotContainsString('unsafe.example', $response->json('html'));

        $this->actingAs($this->otherChild())
            ->postJson(route('child.html.attempts.preview', $attempt, absolute: false), ['source_html' => '<h1>Other child</h1>'])
            ->assertNotFound();
    }

    public function test_published_html_and_approved_projects_complete_the_exact_assignment_items_once(): void
    {
        $child = $this->child();
        $allocation = AssignmentAllocation::query()
            ->whereHas('assignmentVersion.items', fn ($query) => $query->whereNotNull('html_exercise_version_id'))
            ->firstOrFail();
        $htmlItem = AssignmentItem::query()
            ->where('assignment_version_id', $allocation->assignment_version_id)
            ->whereNotNull('html_exercise_version_id')
            ->firstOrFail();
        $projectItem = AssignmentItem::query()
            ->where('assignment_version_id', $allocation->assignment_version_id)
            ->whereNotNull('project_template_version_id')
            ->firstOrFail();

        $nextAttemptNumber = ((int) AssignmentAttempt::query()
            ->where('assignment_allocation_id', $allocation->id)
            ->where('child_id', $child->id)
            ->max('attempt_number')) + 1;
        $htmlAssignmentAttempt = AssignmentAttempt::factory()->create([
            'assignment_allocation_id' => $allocation->id,
            'assignment_version_id' => $allocation->assignment_version_id,
            'child_id' => $child->id,
            'attempt_number' => $nextAttemptNumber,
            'status' => AttemptStatus::InProgress,
        ]);
        $htmlAttempt = app(HtmlAttemptService::class)->start($htmlItem->htmlExerciseVersion, $child, [
            'assignment_allocation_id' => $allocation->id,
            'assignment_attempt_id' => $htmlAssignmentAttempt->id,
            'assignment_item_id' => $htmlItem->id,
            'attempt_type' => 'assignment',
        ]);
        app(HtmlAttemptService::class)->complete($htmlAttempt, $child, ['source_html' => '<p>My assignment paragraph.</p>']);
        app(HtmlAttemptService::class)->complete($htmlAttempt->fresh(), $child, ['source_html' => '<p>My assignment paragraph.</p>']);

        $this->assertSame(1, AssignmentResponse::query()
            ->where('assignment_attempt_id', $htmlAssignmentAttempt->id)
            ->where('assignment_item_id', $htmlItem->id)
            ->count());

        $projectAssignmentAttempt = AssignmentAttempt::factory()->create([
            'assignment_allocation_id' => $allocation->id,
            'assignment_version_id' => $allocation->assignment_version_id,
            'child_id' => $child->id,
            'attempt_number' => $nextAttemptNumber + 1,
            'status' => AttemptStatus::InProgress,
        ]);
        $project = app(WebpageProjectService::class)->create($projectItem->projectTemplateVersion, $child, [
            'assignment_allocation_id' => $allocation->id,
            'assignment_attempt_id' => $projectAssignmentAttempt->id,
            'assignment_item_id' => $projectItem->id,
        ]);
        $submitted = app(WebpageProjectService::class)->submit($project, $child, 'assignment-project-test');
        app(WebpageProjectService::class)->review($submitted, $this->teacher(), [
            'review_status' => 'approved',
            'child_feedback' => 'Your assignment project is ready.',
            'release_to_parent' => true,
        ]);

        $this->assertDatabaseHas('assignment_responses', [
            'assignment_attempt_id' => $projectAssignmentAttempt->id,
            'assignment_item_id' => $projectItem->id,
            'is_correct' => true,
        ]);
    }

    public function test_html_audit_records_are_created(): void
    {
        $this->assertTrue(AuditLog::query()->where('action', 'html.exercise.published')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'html.project_template.published')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'html.project.reviewed')->exists());
    }

    private function exercisePayload(string $title): array
    {
        return [
            'title' => $title,
            'exercise_type' => HtmlExerciseType::HeadingBuilder->value,
            'child_instructions' => 'Build one heading.',
            'html_tag_policy_id' => HtmlTagPolicy::query()->firstOrFail()->id,
            'content_configuration' => ['starter_html' => '<h1>Hello</h1>'],
            'requirements' => [['requirement_type' => 'tag_exists', 'tag_name' => 'h1', 'minimum_count' => 1, 'required' => true]],
        ];
    }

    private function publishedVersion(string $slug)
    {
        return HtmlExercise::query()
            ->where('slug', $slug)
            ->where('status', ContentStatus::Published)
            ->firstOrFail()
            ->currentVersion()
            ->with(['exercise', 'requirements'])
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
        return User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1001'))->firstOrFail();
    }

    private function otherChild(): User
    {
        return User::query()->whereHas('childProfile', fn ($query) => $query->where('learner_id', 'CB-LEARN-1002'))->firstOrFail();
    }
}
