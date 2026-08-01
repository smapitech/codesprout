<?php

namespace App\Models;

use Database\Factories\AssignmentCurriculumLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentCurriculumLink extends Model
{
    /** @use HasFactory<AssignmentCurriculumLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_version_id',
        'curriculum_id',
        'curriculum_world_id',
        'curriculum_unit_id',
        'curriculum_lesson_id',
        'lesson_stage_id',
    ];

    public function assignmentVersion(): BelongsTo
    {
        return $this->belongsTo(AssignmentVersion::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(CurriculumWorld::class, 'curriculum_world_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(CurriculumUnit::class, 'curriculum_unit_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CurriculumLesson::class, 'curriculum_lesson_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(LessonStage::class, 'lesson_stage_id');
    }
}
