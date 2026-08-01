<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingContentItem extends Model
{
    protected $fillable = [
        'typing_exercise_version_id',
        'item_type',
        'prompt_text',
        'expected_text',
        'display_text',
        'normalised_expected_text',
        'audio_path',
        'image_path',
        'target_keys',
        'difficulty_order',
        'display_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'target_keys' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'difficulty_order' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(TypingExerciseVersion::class, 'typing_exercise_version_id');
    }
}
