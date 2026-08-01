<?php

namespace App\Models;

use App\Enums\HtmlValidationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['uuid', 'learner_webpage_project_id', 'revision_number', 'source_html', 'sanitised_html', 'structural_representation', 'source_checksum', 'sanitised_checksum', 'validation_version', 'sanitiser_version', 'validation_status', 'revision_type', 'created_by', 'created_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $revision) => $revision->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'structural_representation' => 'array',
            'validation_status' => HtmlValidationStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearnerWebpageProject::class, 'learner_webpage_project_id');
    }
}
