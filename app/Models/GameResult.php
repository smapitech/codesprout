<?php

namespace App\Models;

use App\Enums\GameCompletionStatus;
use Database\Factories\GameResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameResult extends Model
{
    /** @use HasFactory<GameResultFactory> */
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'correct_actions',
        'incorrect_actions',
        'missed_actions',
        'total_actions',
        'accuracy',
        'completion_time',
        'average_response_time',
        'hints_used',
        'assistance_used',
        'raw_metrics',
        'score',
        'maximum_score',
        'completion_status',
        'calculated_at',
        'released_to_parent',
    ];

    protected function casts(): array
    {
        return [
            'raw_metrics' => 'array',
            'accuracy' => 'decimal:2',
            'score' => 'decimal:2',
            'maximum_score' => 'decimal:2',
            'completion_status' => GameCompletionStatus::class,
            'calculated_at' => 'datetime',
            'released_to_parent' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
