<?php

namespace App\Models;

use Database\Factories\ClassTeacherAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassTeacherAssignment extends Pivot
{
    /** @use HasFactory<ClassTeacherAssignmentFactory> */
    use HasFactory;

    protected $table = 'class_teacher_assignments';

    public $incrementing = true;

    protected $fillable = [
        'class_id',
        'teacher_user_id',
        'is_primary_teacher',
        'role_label',
        'assigned_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_teacher' => 'boolean',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(LearningClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
