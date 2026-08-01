<?php

namespace App\Services\Html;

use App\Enums\ContentStatus;
use App\Models\HtmlTagPolicy;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectTemplatePublicationService
{
    public function __construct(
        private readonly HtmlSanitizer $sanitizer,
        private readonly HtmlAuditService $audit,
    ) {}

    public function createDraft(array $data, User $actor): ProjectTemplate
    {
        return DB::transaction(function () use ($data, $actor): ProjectTemplate {
            $template = ProjectTemplate::query()->create([
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => ContentStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $version = $this->createVersion($template, $data, 1);
            $template->forceFill(['current_version_id' => $version->id])->save();
            $this->audit->record('html.project_template.created', $template, $actor);

            return $template->fresh(['currentVersion.tagPolicy']);
        });
    }

    public function createDraftFrom(ProjectTemplate $template, array $data, User $actor): ProjectTemplateVersion
    {
        return DB::transaction(function () use ($template, $data, $actor): ProjectTemplateVersion {
            $next = ((int) $template->versions()->max('version_number')) + 1;
            $version = $this->createVersion($template, $data, $next);
            $template->forceFill([
                'title' => $data['title'] ?? $template->title,
                'description' => $data['description'] ?? $template->description,
                'status' => ContentStatus::Draft,
                'current_version_id' => $version->id,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->record('html.project_template.version_created', $version, $actor, ['previous_versions' => $next - 1]);

            return $version;
        });
    }

    public function publish(ProjectTemplateVersion $version, User $actor): ProjectTemplateVersion
    {
        return DB::transaction(function () use ($version, $actor): ProjectTemplateVersion {
            $version->loadMissing(['template', 'tagPolicy']);
            $this->validateForPublication($version);
            $version->forceFill([
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'published_by' => $actor->id,
            ])->save();
            $version->template->forceFill([
                'status' => ContentStatus::Published,
                'current_version_id' => $version->id,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->record('html.project_template.published', $version, $actor, ['version_number' => $version->version_number]);

            return $version->fresh(['template', 'tagPolicy']);
        });
    }

    public function archive(ProjectTemplate $template, User $actor): ProjectTemplate
    {
        $template->forceFill(['status' => ContentStatus::Archived, 'archived_at' => now(), 'updated_by' => $actor->id])->save();
        $this->audit->record('html.project_template.archived', $template, $actor);

        return $template->fresh('currentVersion');
    }

    public function validateForPublication(ProjectTemplateVersion $version): void
    {
        if ($version->tagPolicy->status !== ContentStatus::Published) {
            throw ValidationException::withMessages(['html_tag_policy_id' => 'Project templates need a published HTML tag policy.']);
        }

        if (trim($version->sanitised_starter_source) === '') {
            throw ValidationException::withMessages(['starter_source' => 'Add safe starter HTML before publishing.']);
        }
    }

    private function createVersion(ProjectTemplate $template, array $data, int $versionNumber): ProjectTemplateVersion
    {
        $policy = HtmlTagPolicy::query()->findOrFail($data['html_tag_policy_id']);
        $sanitised = $this->sanitizer->sanitise($data['starter_source'] ?? '<h1>My First Webpage</h1><p>My webpage is growing.</p>', $policy);

        return $template->versions()->create([
            'version_number' => $versionNumber,
            'starter_source' => $data['starter_source'],
            'sanitised_starter_source' => $sanitised['sanitised_html'],
            'html_tag_policy_id' => $policy->id,
            'project_configuration' => $data['project_configuration'] ?? ['mode' => 'synced_blocks_code', 'autosave' => true],
            'checklist_configuration' => $data['checklist_configuration'] ?? ['items' => ['Add a heading', 'Add one paragraph', 'Preview safely']],
            'validation_configuration' => $data['validation_configuration'] ?? ['required_tags' => ['h1', 'p']],
            'preview_configuration' => $data['preview_configuration'] ?? ['sandbox' => true],
            'rubric_configuration' => $data['rubric_configuration'] ?? ['criteria' => ['safe_structure', 'alt_text', 'effort']],
            'status' => ContentStatus::Draft,
            'content_checksum' => hash('sha256', json_encode([$data['starter_source'], $sanitised['checksum']], JSON_THROW_ON_ERROR)),
        ]);
    }
}
