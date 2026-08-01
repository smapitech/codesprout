<?php

namespace App\Models;

use Database\Factories\AssessmentRubricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentRubric extends Model
{
    /** @use HasFactory<AssessmentRubricFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentRubricCriterion::class)->orderBy('display_order');
    }
}
