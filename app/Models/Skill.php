<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'mastery_description',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(CurriculumLesson::class, 'lesson_skill', 'skill_id', 'lesson_id')
            ->withPivot(['emphasis_level'])
            ->withTimestamps();
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(LessonStage::class, 'stage_skill', 'skill_id', 'lesson_stage_id')
            ->withTimestamps();
    }
}
