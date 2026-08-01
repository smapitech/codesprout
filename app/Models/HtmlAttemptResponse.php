<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HtmlAttemptResponse extends Model
{
    protected $fillable = ['html_attempt_id', 'response_type', 'bounded_response', 'sanitised_response', 'structural_response', 'input_method', 'display_order'];

    protected function casts(): array
    {
        return ['structural_response' => 'array'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(HtmlAttempt::class, 'html_attempt_id');
    }
}
