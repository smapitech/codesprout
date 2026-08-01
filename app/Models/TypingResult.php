<?php

namespace App\Models;

use App\Enums\TypingValidityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TypingResult extends Model
{
    protected $fillable = [
        'uuid',
        'typing_session_id',
        'child_id',
        'typing_exercise_version_id',
        'expected_character_count',
        'entered_character_count',
        'total_keystrokes',
        'correct_first_attempts',
        'incorrect_first_attempts',
        'corrected_errors',
        'uncorrected_errors',
        'omitted_characters',
        'extra_characters',
        'case_errors',
        'spacing_errors',
        'punctuation_errors',
        'backspace_count',
        'assistance_count',
        'prompt_replay_count',
        'active_duration_ms',
        'characters_per_minute',
        'gross_words_per_minute',
        'adjusted_words_per_minute',
        'first_attempt_accuracy',
        'final_text_accuracy',
        'completion_percentage',
        'validity_status',
        'completed_at',
        'calculation_version',
        'result_checksum',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $result): void {
            $result->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'validity_status' => TypingValidityStatus::class,
            'completed_at' => 'datetime',
            'metadata' => 'array',
            'characters_per_minute' => 'decimal:2',
            'gross_words_per_minute' => 'decimal:2',
            'adjusted_words_per_minute' => 'decimal:2',
            'first_attempt_accuracy' => 'decimal:2',
            'final_text_accuracy' => 'decimal:2',
            'completion_percentage' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'typing_session_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function exerciseVersion(): BelongsTo
    {
        return $this->belongsTo(TypingExerciseVersion::class, 'typing_exercise_version_id');
    }
}
