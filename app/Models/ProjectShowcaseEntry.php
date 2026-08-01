<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectShowcaseEntry extends Model
{
    protected $fillable = ['uuid', 'learner_webpage_project_id', 'approved_revision_id', 'visibility_scope', 'approved_by', 'approved_at', 'withdrawn_at', 'title_override', 'safe_description'];

    protected static function booted(): void
    {
        static::creating(fn (self $entry) => $entry->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearnerWebpageProject::class, 'learner_webpage_project_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ProjectRevision::class, 'approved_revision_id');
    }
}
