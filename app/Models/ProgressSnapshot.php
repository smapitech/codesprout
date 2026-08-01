<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressSnapshot extends Model
{
    protected $fillable = [
        'child_id',
        'snapshot_date',
        'stars',
        'experience',
        'level',
        'streak',
        'curriculum_completion',
        'skill_summary',
        'completed_worlds',
        'badges_earned',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'stars' => 'integer',
            'experience' => 'integer',
            'streak' => 'integer',
            'curriculum_completion' => 'integer',
            'skill_summary' => 'array',
            'completed_worlds' => 'array',
            'badges_earned' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
