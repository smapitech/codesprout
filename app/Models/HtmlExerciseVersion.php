<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\HtmlExerciseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HtmlExerciseVersion extends Model
{
    protected $fillable = ['html_exercise_id', 'version_number', 'exercise_type', 'content_configuration', 'html_tag_policy_id', 'completion_configuration', 'assistance_configuration', 'preview_configuration', 'assessment_configuration', 'accessibility_configuration', 'status', 'published_at', 'published_by', 'archived_at', 'content_checksum'];

    protected function casts(): array
    {
        return [
            'exercise_type' => HtmlExerciseType::class,
            'content_configuration' => 'array',
            'completion_configuration' => 'array',
            'assistance_configuration' => 'array',
            'preview_configuration' => 'array',
            'assessment_configuration' => 'array',
            'accessibility_configuration' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(HtmlExercise::class, 'html_exercise_id');
    }

    public function tagPolicy(): BelongsTo
    {
        return $this->belongsTo(HtmlTagPolicy::class, 'html_tag_policy_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(HtmlExerciseRequirement::class)->orderBy('display_order');
    }
}
