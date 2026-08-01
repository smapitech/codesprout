<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerProgressProfile extends Model
{
    protected $fillable = [
        'child_id',
        'current_level_id',
        'total_stars',
        'total_experience',
        'completed_missions',
        'completed_lessons',
        'completed_units',
        'completed_worlds',
        'current_streak',
        'longest_streak',
        'last_learning_date',
        'progress_calculated_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'total_stars' => 'integer',
            'total_experience' => 'integer',
            'completed_missions' => 'integer',
            'completed_lessons' => 'integer',
            'completed_units' => 'integer',
            'completed_worlds' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_learning_date' => 'date',
            'progress_calculated_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function currentLevel(): BelongsTo
    {
        return $this->belongsTo(LearnerLevel::class, 'current_level_id');
    }
}
