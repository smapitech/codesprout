<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\GameVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameVersion extends Model
{
    /** @use HasFactory<GameVersionFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'game_definition_id',
        'version_number',
        'configuration',
        'instruction_content',
        'difficulty_configuration',
        'supported_input_methods',
        'status',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'configuration' => 'array',
            'instruction_content' => 'array',
            'difficulty_configuration' => 'array',
            'supported_input_methods' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(GameDefinition::class, 'game_definition_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }
}
