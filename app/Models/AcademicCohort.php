<?php

namespace App\Models;

use Database\Factories\AcademicCohortFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicCohort extends Model
{
    /** @use HasFactory<AcademicCohortFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'academic_year',
        'starts_on',
        'ends_on',
        'is_current',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(LearningClass::class, 'academic_cohort_id');
    }
}
