<?php

namespace App\Models;

use Database\Factories\AssessmentRubricCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRubricCriterion extends Model
{
    /** @use HasFactory<AssessmentRubricCriterionFactory> */
    use HasFactory;

    protected $fillable = [
        'assessment_rubric_id',
        'title',
        'description',
        'maximum_points',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'maximum_points' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'assessment_rubric_id');
    }
}
