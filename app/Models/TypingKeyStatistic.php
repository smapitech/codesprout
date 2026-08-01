<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingKeyStatistic extends Model
{
    protected $fillable = [
        'child_id',
        'key_identifier',
        'keyboard_layout',
        'input_method',
        'attempts',
        'first_attempt_correct',
        'corrected_attempts',
        'recent_accuracy',
        'highest_supported_accuracy',
        'average_response_time_ms',
        'mastery_label',
        'last_practised_at',
        'calculated_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'recent_accuracy' => 'decimal:2',
            'highest_supported_accuracy' => 'decimal:2',
            'last_practised_at' => 'datetime',
            'calculated_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
