<?php

namespace App\Models;

use Database\Factories\GameSessionRoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSessionRound extends Model
{
    /** @use HasFactory<GameSessionRoundFactory> */
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'round_number',
        'round_data',
        'response_data',
        'is_correct',
        'response_time_ms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'round_data' => 'array',
            'response_data' => 'array',
            'is_correct' => 'boolean',
            'response_time_ms' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
