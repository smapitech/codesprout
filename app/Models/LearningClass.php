<?php

namespace App\Models;

use Database\Factories\LearningClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningClass extends Model
{
    /** @use HasFactory<LearningClassFactory> */
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'academic_cohort_id',
        'curriculum_world_id',
        'class_code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(AcademicCohort::class, 'academic_cohort_id');
    }

    public function curriculumWorld(): BelongsTo
    {
        return $this->belongsTo(CurriculumWorld::class, 'curriculum_world_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_teacher_assignments', 'class_id', 'teacher_user_id')
            ->using(ClassTeacherAssignment::class)
            ->withPivot([
                'is_primary_teacher',
                'role_label',
                'assigned_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_enrolments', 'class_id', 'child_user_id')
            ->using(ClassEnrollment::class)
            ->withPivot([
                'status',
                'is_primary_class',
                'enrolled_by_user_id',
                'enrolled_at',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(ClassTeacherAssignment::class, 'class_id');
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }
}
