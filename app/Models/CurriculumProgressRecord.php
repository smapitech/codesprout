<?php

namespace App\Models;

use App\Enums\LearnerProgressStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumProgressRecord extends Model
{
    protected $fillable = [
        'child_id',
        'curriculum_id',
        'curriculum_world_id',
        'curriculum_unit_id',
        'curriculum_lesson_id',
        'lesson_stage_id',
        'status',
        'completion_percentage',
        'completed_required_items',
        'total_required_items',
        'started_at',
        'completed_at',
        'last_activity_at',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LearnerProgressStatus::class,
            'completion_percentage' => 'integer',
            'completed_required_items' => 'integer',
            'total_required_items' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'calculated_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
