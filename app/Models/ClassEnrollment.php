<?php

namespace App\Models;

use Database\Factories\ClassEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassEnrollment extends Pivot
{
    /** @use HasFactory<ClassEnrollmentFactory> */
    use HasFactory;

    protected $table = 'class_enrolments';

    public $incrementing = true;

    protected $fillable = [
        'class_id',
        'child_user_id',
        'status',
        'is_primary_class',
        'enrolled_by_user_id',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_class' => 'boolean',
            'enrolled_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(LearningClass::class, 'class_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by_user_id');
    }
}
