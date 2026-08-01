<?php

namespace App\Models;

use App\Enums\HtmlProjectMode;
use App\Enums\HtmlProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LearnerWebpageProject extends Model
{
    protected $fillable = ['uuid', 'child_id', 'preview_actor_id', 'project_template_version_id', 'lesson_stage_id', 'assignment_allocation_id', 'assignment_attempt_id', 'assignment_item_id', 'title', 'project_mode', 'status', 'current_revision_number', 'state_version', 'last_saved_at', 'first_started_at', 'paused_at', 'submitted_at', 'completed_at', 'approved_at', 'approved_by', 'idempotency_key', 'metadata'];

    protected static function booted(): void
    {
        static::creating(fn (self $project) => $project->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'project_mode' => HtmlProjectMode::class,
            'status' => HtmlProjectStatus::class,
            'last_saved_at' => 'datetime',
            'first_started_at' => 'datetime',
            'paused_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateVersion::class, 'project_template_version_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ProjectRevision::class)->orderBy('revision_number');
    }

    public function autosaves(): HasMany
    {
        return $this->hasMany(ProjectAutosave::class);
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(ProjectRevision::class)->latestOfMany('revision_number');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProjectReview::class);
    }

    public function showcaseEntry(): HasOne
    {
        return $this->hasOne(ProjectShowcaseEntry::class);
    }
}
