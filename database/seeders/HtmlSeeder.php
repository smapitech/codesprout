<?php

namespace Database\Seeders;

use App\Enums\HtmlAttemptStatus;
use App\Enums\HtmlExerciseType;
use App\Enums\HtmlProjectStatus;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentItem;
use App\Models\AuditLog;
use App\Models\HtmlExercise;
use App\Models\HtmlTagPolicy;
use App\Models\LearnerWebpageProject;
use App\Models\LessonStage;
use App\Models\ProjectTemplate;
use App\Models\User;
use App\Services\Html\HtmlAttemptService;
use App\Services\Html\HtmlExercisePublicationService;
use App\Services\Html\HtmlTagPolicyService;
use App\Services\Html\ProjectTemplatePublicationService;
use App\Services\Html\WebpageProjectService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HtmlSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearExistingHtmlData();

        $admin = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
        $teacher = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
        $children = User::role('child')->orderBy('name')->get();
        $child = $children->firstOrFail();
        $otherChild = $children->skip(1)->first() ?? $child;
        $stage = LessonStage::query()->published()->first();

        $policy = $this->seedPolicy($admin);
        $versions = $this->seedExercises($admin, $policy);
        $templates = $this->seedTemplates($admin, $policy);

        $attempts = app(HtmlAttemptService::class);
        $projects = app(WebpageProjectService::class);

        $attempt = $attempts->start($versions['heading-adventure'], $child, ['lesson_stage_id' => $stage?->id]);
        $attempts->complete($attempt, $child, [
            'source_html' => '<h1>My First Webpage</h1><p>My webpage is growing.</p>',
            'input_method' => 'guided_code',
            'active_duration_ms' => 120000,
            'idempotency_key' => 'html-seed-heading',
        ]);

        $unsafe = $attempts->start($versions['fix-the-tag'], $child);
        $attempts->complete($unsafe, $child, [
            'source_html' => '<h1 onclick="alert(1)">Oops</h1><script>alert(1)</script>',
            'input_method' => 'guided_code',
            'active_duration_ms' => 60000,
            'idempotency_key' => 'html-seed-unsafe',
        ]);
        $unsafe->fresh()->forceFill(['status' => HtmlAttemptStatus::Invalidated])->save();

        $attempts->start($versions['meet-the-angle-brackets'], $teacher, ['preview' => true]);
        $attempts->start($versions['opening-and-closing-tags'], $admin, ['preview' => true]);

        $project = $projects->create($templates['my-first-webpage'], $child, ['lesson_stage_id' => $stage?->id]);
        $projects->autosave($project, $child, [
            'autosave_uuid' => (string) Str::uuid(),
            'state_version' => $project->state_version,
            'source_html' => '<h1>My First Webpage</h1><p>I can build with HTML.</p>',
            'client_instance_id' => 'seed',
        ]);
        $project = $projects->submit($project->fresh(), $child, 'html-project-seed-submit');
        $projects->review($project, $teacher, [
            'review_status' => 'approved',
            'child_feedback' => 'Wonderful work. Your webpage has a clear heading and paragraph.',
            'teacher_only_notes' => 'Seeded example kept parent-safe.',
            'release_to_parent' => true,
        ]);

        $paused = $projects->create($templates['a-friendly-robot'], $child);
        $projects->pause($paused, $child);

        $changes = $projects->create($templates['computer-care-guide'], $otherChild);
        $projects->autosave($changes, $otherChild, [
            'autosave_uuid' => (string) Str::uuid(),
            'state_version' => $changes->state_version,
            'source_html' => '<h1>Computer Care</h1><p>Use clean hands.</p>',
        ]);
        $changes = $projects->submit($changes->fresh(), $otherChild);
        $projects->review($changes, $teacher, [
            'review_status' => 'changes_requested',
            'child_feedback' => 'Lovely start. Please add one list of computer care steps.',
            'teacher_only_notes' => 'Needs list checkpoint.',
            'requested_changes' => ['Add a list'],
            'release_to_parent' => false,
        ]);

        LearnerWebpageProject::query()->create([
            'child_id' => $child->id,
            'project_template_version_id' => $templates['journey-to-space']->id,
            'title' => 'Expired Space Page',
            'project_mode' => 'guided_code',
            'status' => HtmlProjectStatus::Expired,
            'first_started_at' => now()->subDays(8),
            'metadata' => ['seed_state' => 'expired'],
        ]);

        $this->linkHtmlAssignment($versions['paragraph-builder'], $templates['my-favourite-animal'], $child, $stage);

        AuditLog::query()->create([
            'actor_user_id' => $admin->id,
            'action' => 'html.seeded.phase_seven',
            'subject_type' => HtmlExercise::class,
            'subject_id' => HtmlExercise::query()->first()?->id,
            'metadata' => [
                'tag_policies' => HtmlTagPolicy::query()->count(),
                'exercises' => HtmlExercise::query()->count(),
                'templates' => ProjectTemplate::query()->count(),
            ],
            'created_at' => now(),
        ]);
    }

    private function seedPolicy(User $admin): HtmlTagPolicy
    {
        return app(HtmlTagPolicyService::class)->createPublished([
            'name' => 'CodeSprout Starter HTML',
            'allowed_tags' => ['h1', 'h2', 'h3', 'p', 'br', 'hr', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'div', 'section'],
            'allowed_attributes' => [
                '*' => ['title'],
                'a' => ['href', 'title', 'rel'],
                'img' => ['src', 'alt', 'width', 'height', 'title'],
            ],
            'allowed_protocols' => ['https', 'mailto'],
        ], $admin);
    }

    private function seedExercises(User $admin, HtmlTagPolicy $policy): array
    {
        $publication = app(HtmlExercisePublicationService::class);
        $rows = [
            ['Coding Symbol Explorer', HtmlExerciseType::SymbolRecognition, 'Find the angle brackets.', [['tag_exists', 'p']]],
            ['Meet the Angle Brackets', HtmlExerciseType::SymbolMatching, 'Match < and > to make a tag.', [['tag_exists', 'p']]],
            ['Opening and Closing Tags', HtmlExerciseType::OpeningClosingMatch, 'Match opening and closing tags.', [['tag_exists', 'p']]],
            ['Heading Adventure', HtmlExerciseType::HeadingBuilder, 'Build a friendly heading.', [['tag_exists', 'h1']]],
            ['Paragraph Builder', HtmlExerciseType::ParagraphBuilder, 'Put text inside paragraph tags.', [['tag_exists', 'p']]],
            ['Strong and Emphasised Words', HtmlExerciseType::GuidedCodeCompletion, 'Try strong and em tags.', [['tag_exists', 'strong'], ['tag_exists', 'em']]],
            ['List Maker', HtmlExerciseType::ListBuilder, 'Place list items inside a list.', [['tag_exists', 'ul'], ['tag_exists', 'li']]],
            ['Safe Link Explorer', HtmlExerciseType::LinkBuilder, 'Create a safe lesson link.', [['tag_exists', 'a'], ['attribute_exists', 'a', 'href']]],
            ['Image and Alt Text', HtmlExerciseType::ImageBuilder, 'Add an image description.', [['tag_exists', 'img'], ['alt_text', 'img', 'alt']]],
            ['Page Structure', HtmlExerciseType::DocumentStructureBuilder, 'Build a small page structure.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
            ['Fix the Tag', HtmlExerciseType::CodeRepair, 'Fix one small HTML issue.', [['tag_exists', 'h1']]],
            ['Nesting Adventure', HtmlExerciseType::NestingOrder, 'Keep tags tucked inside each other.', [['tag_exists', 'section'], ['tag_exists', 'p']]],
            ['Build with Blocks', HtmlExerciseType::VisualBlockBuilder, 'Use blocks to make safe HTML.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
            ['My First Webpage', HtmlExerciseType::StructuredFreeCode, 'Build a starter webpage.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
            ['Animal Information Page', HtmlExerciseType::ProjectCheckpoint, 'Add animal facts safely.', [['tag_exists', 'h1'], ['tag_exists', 'ul']]],
            ['Space Explorer Page', HtmlExerciseType::ProjectCheckpoint, 'Create a space page.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
            ['Computer Safety Page', HtmlExerciseType::ProjectCheckpoint, 'Share computer care rules.', [['tag_exists', 'h1'], ['tag_exists', 'ol']]],
            ['Celebration Card', HtmlExerciseType::ProjectSubmission, 'Make a kind celebration card.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
            ['Mini Story Page', HtmlExerciseType::GuidedCodeCompletion, 'Write a tiny story page.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
            ['Starter Portfolio', HtmlExerciseType::FormalHtmlAssessment, 'Show what tags you know.', [['tag_exists', 'h1'], ['tag_exists', 'p']]],
        ];

        $versions = [];
        foreach ($rows as [$title, $type, $instructions, $requirements]) {
            $exercise = $publication->createDraft([
                'title' => $title,
                'exercise_type' => $type->value,
                'description' => 'Phase 7 seeded HTML learning activity.',
                'child_instructions' => $instructions,
                'teacher_instructions' => 'Use this for short structured HTML practice.',
                'html_tag_policy_id' => $policy->id,
                'content_configuration' => ['starter_html' => '<h1>Hello webpage</h1><p>My webpage is growing.</p>'],
                'requirements' => array_map(fn (array $row): array => [
                    'requirement_type' => $row[0],
                    'tag_name' => $row[1] ?? null,
                    'attribute_name' => $row[2] ?? null,
                    'minimum_count' => 1,
                    'required' => true,
                ], $requirements),
            ], $admin);
            $versions[$exercise->slug] = $publication->publish($exercise->currentVersion, $admin);
        }

        return $versions;
    }

    private function seedTemplates(User $admin, HtmlTagPolicy $policy): array
    {
        $publication = app(ProjectTemplatePublicationService::class);
        $titles = ['My First Webpage', 'My Favourite Animal', 'A Friendly Robot', 'Journey to Space', 'Computer Care Guide', 'Healthy Food List', 'My Learning Goals', 'Mini Story Page', 'Celebration Card', 'Starter Learning Portfolio'];
        $versions = [];

        foreach ($titles as $title) {
            $template = $publication->createDraft([
                'title' => $title,
                'description' => 'A safe starter template for early HTML learners.',
                'html_tag_policy_id' => $policy->id,
                'starter_source' => '<h1>'.$title.'</h1><p>My webpage is growing.</p>',
            ], $admin);
            $versions[$template->slug] = $publication->publish($template->currentVersion, $admin);
        }

        return $versions;
    }

    private function linkHtmlAssignment($version, $templateVersion, User $child, ?LessonStage $stage): void
    {
        $allocation = AssignmentAllocation::query()
            ->with('assignmentVersion.items')
            ->whereHas('classroom.learners', fn ($query) => $query->where('users.id', $child->id))
            ->first();

        $items = $allocation?->assignmentVersion->items;
        $htmlItem = $items?->first();
        $projectItem = $items?->skip(1)->first();
        if (! $allocation || ! $htmlItem instanceof AssignmentItem) {
            return;
        }

        $htmlItem->forceFill([
            'html_exercise_version_id' => $version->id,
            'project_template_version_id' => null,
            'configuration' => array_merge($htmlItem->configuration ?? [], ['html_assignment' => true, 'lesson_stage_id' => $stage?->id]),
        ])->save();

        if ($projectItem instanceof AssignmentItem) {
            $projectItem->forceFill([
                'html_exercise_version_id' => null,
                'project_template_version_id' => $templateVersion->id,
                'configuration' => array_merge($projectItem->configuration ?? [], ['html_project_assignment' => true, 'lesson_stage_id' => $stage?->id]),
            ])->save();
        }
    }

    private function clearExistingHtmlData(): void
    {
        if (Schema::hasColumn('assignment_items', 'html_exercise_version_id')) {
            AssignmentItem::query()->whereNotNull('html_exercise_version_id')->update([
                'html_exercise_version_id' => null,
                'project_template_version_id' => null,
            ]);
        }

        DB::table('project_showcase_entries')->delete();
        DB::table('project_reviews')->delete();
        DB::table('project_checkpoints')->delete();
        DB::table('html_requirement_results')->delete();
        DB::table('html_validation_results')->delete();
        DB::table('html_attempt_responses')->delete();
        DB::table('html_attempts')->delete();
        DB::table('project_autosaves')->delete();
        DB::table('project_revisions')->delete();
        DB::table('learner_webpage_projects')->delete();
        DB::table('project_templates')->update(['current_version_id' => null]);
        DB::table('project_template_versions')->delete();
        DB::table('project_templates')->delete();
        DB::table('html_exercises')->update(['current_version_id' => null]);
        DB::table('html_exercise_requirements')->delete();
        DB::table('html_exercise_versions')->delete();
        DB::table('html_exercises')->delete();
        DB::table('html_tag_policies')->delete();
    }
}
