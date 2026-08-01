<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HtmlRequirementResult extends Model
{
    protected $fillable = ['html_validation_result_id', 'html_exercise_requirement_id', 'rule_identifier', 'outcome', 'evidence_summary', 'safe_guidance_code', 'display_order'];

    protected function casts(): array
    {
        return ['evidence_summary' => 'array'];
    }

    public function validationResult(): BelongsTo
    {
        return $this->belongsTo(HtmlValidationResult::class, 'html_validation_result_id');
    }
}
