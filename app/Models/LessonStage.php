<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\InteractionType;
use App\Enums\StageType;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\LessonStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LessonStage extends Model
{
    /** @use HasFactory<LessonStageFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'slug',
        'stage_type',
        'interaction_type',
        'instruction_text',
        'encouragement_text',
        'teacher_guidance',
        'audio_instruction_path',
        'estimated_minutes',
        'difficulty_level',
        'star_value',
        'is_required',
        'display_order',
        'status',
        'published_at',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'stage_type' => StageType::class,
            'interaction_type' => InteractionType::class,
            'estimated_minutes' => 'integer',
            'difficulty_level' => DifficultyLevel::class,
            'star_value' => 'integer',
            'is_required' => 'boolean',
            'display_order' => 'integer',
            'published_at' => 'datetime',
            'configuration' => 'array',
            'status' => ContentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CurriculumLesson::class, 'lesson_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'stage_skill', 'lesson_stage_id', 'skill_id')
            ->withTimestamps();
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'lesson_stage_prerequisites',
            'lesson_stage_id',
            'prerequisite_stage_id',
        )->withTimestamps();
    }

    public function dependentStages(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'lesson_stage_prerequisites',
            'prerequisite_stage_id',
            'lesson_stage_id',
        )->withTimestamps();
    }
}
