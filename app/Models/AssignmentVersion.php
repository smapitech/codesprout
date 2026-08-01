<?php

namespace App\Models;

use App\Enums\AssignmentFeedbackMode;
use App\Enums\AssignmentScoringMethod;
use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\AssignmentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentVersion extends Model
{
    /** @use HasFactory<AssignmentVersionFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'assignment_id',
        'version_number',
        'title',
        'short_description',
        'child_instructions',
        'teacher_instructions',
        'audio_instruction_path',
        'estimated_minutes',
        'difficulty_level',
        'total_points',
        'default_attempt_limit',
        'feedback_mode',
        'scoring_method',
        'status',
        'published_at',
        'published_by',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'estimated_minutes' => 'integer',
            'total_points' => 'integer',
            'default_attempt_limit' => 'integer',
            'difficulty_level' => DifficultyLevel::class,
            'feedback_mode' => AssignmentFeedbackMode::class,
            'scoring_method' => AssignmentScoringMethod::class,
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssignmentItem::class)->orderBy('display_order');
    }

    public function curriculumLinks(): HasMany
    {
        return $this->hasMany(AssignmentCurriculumLink::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AssignmentAllocation::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'assignment_skill', 'assignment_version_id', 'skill_id')
            ->withPivot(['emphasis_level'])
            ->withTimestamps();
    }
}
