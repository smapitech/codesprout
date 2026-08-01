<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\DifficultyLevel;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\CurriculumLessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumLesson extends Model
{
    /** @use HasFactory<CurriculumLessonFactory> */
    use HasContentStatus, HasFactory;

    protected $table = 'lessons';

    protected $fillable = [
        'unit_id',
        'title',
        'slug',
        'lesson_number',
        'description',
        'teacher_notes',
        'learner_objective',
        'estimated_minutes',
        'difficulty_level',
        'display_order',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'lesson_number' => 'integer',
            'estimated_minutes' => 'integer',
            'display_order' => 'integer',
            'published_at' => 'datetime',
            'difficulty_level' => DifficultyLevel::class,
            'status' => ContentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CurriculumUnit::class, 'unit_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(LessonStage::class, 'lesson_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'lesson_skill', 'lesson_id', 'skill_id')
            ->withPivot(['emphasis_level'])
            ->withTimestamps();
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'lesson_prerequisites',
            'lesson_id',
            'prerequisite_lesson_id',
        )->withTimestamps();
    }

    public function dependentLessons(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'lesson_prerequisites',
            'prerequisite_lesson_id',
            'lesson_id',
        )->withTimestamps();
    }
}
