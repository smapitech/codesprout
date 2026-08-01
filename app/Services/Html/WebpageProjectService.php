<?php

namespace App\Services\Html;

use App\Enums\ContentStatus;
use App\Enums\HtmlProjectMode;
use App\Enums\HtmlProjectStatus;
use App\Enums\HtmlValidationStatus;
use App\Events\Html\WebpageProjectCompleted;
use App\Models\LearnerWebpageProject;
use App\Models\ProjectAutosave;
use App\Models\ProjectReview;
use App\Models\ProjectRevision;
use App\Models\ProjectShowcaseEntry;
use App\Models\ProjectTemplateVersion;
use App\Models\AssignmentItem;
use App\Models\AssignmentResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WebpageProjectService
{
    public function __construct(
        private readonly HtmlSanitizer $sanitizer,
        private readonly HtmlAuditService $audit,
    ) {}

    public function create(ProjectTemplateVersion $version, User $child, array $context = []): LearnerWebpageProject
    {
        abort_unless($version->status === ContentStatus::Published, 404);

        return DB::transaction(function () use ($version, $child, $context): LearnerWebpageProject {
            $project = LearnerWebpageProject::query()->create([
                'child_id' => ($context['preview'] ?? false) ? null : $child->id,
                'preview_actor_id' => ($context['preview'] ?? false) ? $child->id : null,
                'project_template_version_id' => $version->id,
                'lesson_stage_id' => $context['lesson_stage_id'] ?? null,
                'assignment_allocation_id' => $context['assignment_allocation_id'] ?? null,
                'assignment_attempt_id' => $context['assignment_attempt_id'] ?? null,
                'assignment_item_id' => $context['assignment_item_id'] ?? null,
                'title' => $context['title'] ?? $version->template->title,
                'project_mode' => HtmlProjectMode::tryFrom($version->project_configuration['mode'] ?? '') ?? HtmlProjectMode::SyncedBlocksCode,
                'status' => ($context['preview'] ?? false) ? HtmlProjectStatus::AdministratorPreview : HtmlProjectStatus::Active,
                'first_started_at' => now(),
                'idempotency_key' => $context['idempotency_key'] ?? null,
                'metadata' => ['preview' => (bool) ($context['preview'] ?? false)],
            ]);

            $this->createRevision($project, $version->starter_source, $child, 'initial_template');

            return $project->fresh(['templateVersion.template', 'latestRevision']);
        });
    }

    public function autosave(LearnerWebpageProject $project, User $actor, array $payload): LearnerWebpageProject
    {
        $this->assertOwner($project, $actor);

        return DB::transaction(function () use ($project, $actor, $payload): LearnerWebpageProject {
            $locked = LearnerWebpageProject::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [HtmlProjectStatus::Active, HtmlProjectStatus::Paused, HtmlProjectStatus::ChangesRequested, HtmlProjectStatus::Resubmitted], true)) {
                throw ValidationException::withMessages(['project' => 'This project is already submitted, so autosave cannot change it.']);
            }

            if ((int) $payload['state_version'] !== (int) $locked->state_version) {
                throw ValidationException::withMessages(['state_version' => 'A newer version of this project was found. Please refresh carefully.']);
            }

            $existing = ProjectAutosave::query()
                ->where('learner_webpage_project_id', $locked->id)
                ->where('autosave_uuid', $payload['autosave_uuid'])
                ->first();

            if ($existing) {
                return $locked->fresh(['latestRevision']);
            }

            $nextVersion = $locked->state_version + 1;
            ProjectAutosave::query()->create([
                'learner_webpage_project_id' => $locked->id,
                'autosave_uuid' => $payload['autosave_uuid'],
                'based_on_state_version' => $locked->state_version,
                'resulting_state_version' => $nextVersion,
                'bounded_source' => $payload['source_html'],
                'source_checksum' => hash('sha256', $payload['source_html']),
                'client_instance_id' => $payload['client_instance_id'] ?? null,
                'saved_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);

            $this->createRevision($locked, $payload['source_html'], $actor, 'autosave');
            $locked->forceFill(['state_version' => $nextVersion, 'last_saved_at' => now(), 'status' => HtmlProjectStatus::Active])->save();

            return $locked->fresh(['latestRevision']);
        });
    }

    public function pause(LearnerWebpageProject $project, User $actor): void
    {
        $this->assertOwner($project, $actor);
        $project->forceFill(['status' => HtmlProjectStatus::Paused, 'paused_at' => now(), 'state_version' => $project->state_version + 1])->save();
    }

    public function resume(LearnerWebpageProject $project, User $actor): void
    {
        $this->assertOwner($project, $actor);
        if ($project->status !== HtmlProjectStatus::Paused) {
            throw ValidationException::withMessages(['project' => 'This project is not paused right now.']);
        }
        $project->forceFill(['status' => HtmlProjectStatus::Active, 'state_version' => $project->state_version + 1])->save();
    }

    public function submit(LearnerWebpageProject $project, User $actor, ?string $idempotencyKey = null): LearnerWebpageProject
    {
        $this->assertOwner($project, $actor);

        return DB::transaction(function () use ($project, $actor, $idempotencyKey): LearnerWebpageProject {
            $locked = LearnerWebpageProject::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, [HtmlProjectStatus::AwaitingReview, HtmlProjectStatus::Approved, HtmlProjectStatus::Completed], true)) {
                return $locked->fresh(['latestRevision', 'reviews']);
            }

            $revision = $locked->latestRevision()->first();
            if (! $revision || $revision->validation_status === HtmlValidationStatus::Unsafe) {
                throw ValidationException::withMessages(['project' => 'Your webpage needs one safe correction before it can be submitted.']);
            }

            $locked->forceFill([
                'status' => HtmlProjectStatus::AwaitingReview,
                'submitted_at' => now(),
                'current_revision_number' => $revision->revision_number,
                'state_version' => $locked->state_version + 1,
                'idempotency_key' => $idempotencyKey ?? $locked->idempotency_key,
            ])->save();

            $this->audit->record('html.project.submitted', $locked, $actor, ['revision_number' => $revision->revision_number]);

            return $locked->fresh(['latestRevision', 'templateVersion.template']);
        });
    }

    public function review(LearnerWebpageProject $project, User $teacher, array $payload): ProjectReview
    {
        return DB::transaction(function () use ($project, $teacher, $payload): ProjectReview {
            $project = LearnerWebpageProject::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $revision = $project->latestRevision()->firstOrFail();
            $status = $payload['review_status'];

            $review = ProjectReview::query()->create([
                'learner_webpage_project_id' => $project->id,
                'reviewed_revision_id' => $revision->id,
                'reviewer_id' => $teacher->id,
                'review_status' => $status,
                'rubric_result' => $payload['rubric_result'] ?? [],
                'child_feedback' => $payload['child_feedback'],
                'teacher_only_notes' => $payload['teacher_only_notes'] ?? null,
                'requested_changes' => $payload['requested_changes'] ?? [],
                'reviewed_at' => now(),
                'released_at' => $payload['release_to_parent'] ?? true ? now() : null,
            ]);

            if ($status === 'changes_requested') {
                $project->forceFill(['status' => HtmlProjectStatus::ChangesRequested, 'state_version' => $project->state_version + 1])->save();
            }

            if ($status === 'approved') {
                $project->forceFill([
                    'status' => HtmlProjectStatus::Completed,
                    'approved_at' => now(),
                    'completed_at' => now(),
                    'approved_by' => $teacher->id,
                    'state_version' => $project->state_version + 1,
                ])->save();

                ProjectShowcaseEntry::query()->updateOrCreate(
                    ['learner_webpage_project_id' => $project->id],
                    [
                        'approved_revision_id' => $revision->id,
                        'visibility_scope' => 'private',
                        'approved_by' => $teacher->id,
                        'approved_at' => now(),
                        'title_override' => $project->title,
                        'safe_description' => 'Teacher-approved starter webpage project.',
                    ],
                );

                $this->recordAssignmentCompletion($project);

                event(new WebpageProjectCompleted($project->fresh(['latestRevision', 'templateVersion.template'])));
            }

            $this->audit->record('html.project.reviewed', $project, $teacher, ['review_status' => $status]);

            return $review->fresh('revision');
        });
    }

    private function createRevision(LearnerWebpageProject $project, string $source, User $actor, string $type): ProjectRevision
    {
        $project->loadMissing('templateVersion.tagPolicy');
        $sanitised = $this->sanitizer->sanitise($source, $project->templateVersion->tagPolicy);
        $revisionNumber = ((int) $project->revisions()->max('revision_number')) + 1;

        return $project->revisions()->create([
            'revision_number' => $revisionNumber,
            'source_html' => $source,
            'sanitised_html' => $sanitised['sanitised_html'],
            'structural_representation' => $sanitised['structure'],
            'source_checksum' => hash('sha256', $source),
            'sanitised_checksum' => $sanitised['checksum'],
            'validation_status' => count($sanitised['issues']) > 0 ? HtmlValidationStatus::Unsafe : HtmlValidationStatus::Valid,
            'revision_type' => $type,
            'created_by' => $actor->id,
            'created_at' => now(),
        ]);
    }

    private function assertOwner(LearnerWebpageProject $project, User $actor): void
    {
        if ($project->child_id && $project->child_id !== $actor->id) {
            abort(403);
        }

        if ($project->preview_actor_id && $project->preview_actor_id !== $actor->id && ! $actor->hasRole('administrator')) {
            abort(403);
        }
    }

    private function recordAssignmentCompletion(LearnerWebpageProject $project): void
    {
        if (! $project->assignment_attempt_id || ! $project->assignment_item_id) {
            return;
        }

        $item = AssignmentItem::query()->find($project->assignment_item_id);
        if (! $item || (int) $item->project_template_version_id !== (int) $project->project_template_version_id) {
            return;
        }

        AssignmentResponse::query()->updateOrCreate(
            ['assignment_attempt_id' => $project->assignment_attempt_id, 'assignment_item_id' => $item->id],
            [
                'response_data' => ['linked_webpage_project' => $project->uuid, 'status' => 'teacher_approved'],
                'text_response' => 'Teacher-approved webpage project completed',
                'is_correct' => true,
                'auto_score' => $item->points,
                'manual_score' => 0,
            ],
        );
    }
}
