<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HtmlExerciseRequirement extends Model
{
    protected $fillable = ['html_exercise_version_id', 'requirement_type', 'tag_name', 'attribute_name', 'expected_value', 'parent_requirement_id', 'minimum_count', 'maximum_count', 'display_order', 'required', 'scoring_weight', 'safe_configuration'];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'safe_configuration' => 'array',
            'scoring_weight' => 'decimal:2',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(HtmlExerciseVersion::class, 'html_exercise_version_id');
    }
}
