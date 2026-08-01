<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTemplateVersion extends Model
{
    protected $fillable = ['project_template_id', 'version_number', 'starter_source', 'sanitised_starter_source', 'html_tag_policy_id', 'project_configuration', 'checklist_configuration', 'validation_configuration', 'preview_configuration', 'rubric_configuration', 'status', 'published_at', 'published_by', 'archived_at', 'content_checksum'];

    protected function casts(): array
    {
        return [
            'project_configuration' => 'array',
            'checklist_configuration' => 'array',
            'validation_configuration' => 'array',
            'preview_configuration' => 'array',
            'rubric_configuration' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function tagPolicy(): BelongsTo
    {
        return $this->belongsTo(HtmlTagPolicy::class, 'html_tag_policy_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(LearnerWebpageProject::class);
    }

    public function getPublishedModeAttribute(): string
    {
        return $this->project_configuration['mode'] ?? 'synced_blocks_code';
    }
}
