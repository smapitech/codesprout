<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingProgressSnapshot extends Model
{
    protected $fillable = [
        'child_id',
        'snapshot_date',
        'overall_accuracy',
        'first_attempt_accuracy',
        'final_text_accuracy',
        'characters_per_minute',
        'gross_words_per_minute',
        'practised_keys',
        'confident_keys',
        'words_completed',
        'sentences_completed',
        'practice_minutes',
        'input_method_summary',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'input_method_summary' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
