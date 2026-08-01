<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TypingBackspacePolicy;
use App\Enums\TypingCorrectionPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypingExerciseVersion extends Model
{
    protected $fillable = [
        'typing_exercise_id',
        'version_number',
        'typing_difficulty_profile_id',
        'content_configuration',
        'case_sensitive',
        'backspace_policy',
        'correction_policy',
        'input_method_policy',
        'timer_configuration',
        'completion_criteria',
        'accuracy_requirement',
        'speed_requirement',
        'assistance_configuration',
        'adaptive_configuration',
        'status',
        'published_at',
        'published_by',
        'archived_at',
        'content_checksum',
    ];

    protected function casts(): array
    {
        return [
            'content_configuration' => 'array',
            'timer_configuration' => 'array',
            'completion_criteria' => 'array',
            'assistance_configuration' => 'array',
            'adaptive_configuration' => 'array',
            'backspace_policy' => TypingBackspacePolicy::class,
            'correction_policy' => TypingCorrectionPolicy::class,
            'status' => ContentStatus::class,
            'accuracy_requirement' => 'decimal:2',
            'speed_requirement' => 'decimal:2',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(TypingExercise::class, 'typing_exercise_id');
    }

    public function difficultyProfile(): BelongsTo
    {
        return $this->belongsTo(TypingDifficultyProfile::class, 'typing_difficulty_profile_id');
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(TypingContentItem::class)->orderBy('display_order');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'typing_exercise_skill')
            ->withPivot('emphasis_level')
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TypingSession::class);
    }
}
