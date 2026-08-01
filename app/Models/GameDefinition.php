<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\GameCategory;
use App\Enums\GameType;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\GameDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GameDefinition extends Model
{
    /** @use HasFactory<GameDefinitionFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'category',
        'game_type',
        'description',
        'instructions',
        'status',
        'visibility',
        'created_by',
        'updated_by',
        'current_version_id',
        'archived_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $game): void {
            $game->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'category' => GameCategory::class,
            'game_type' => GameType::class,
            'status' => ContentStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function versions(): HasMany
    {
        return $this->hasMany(GameVersion::class)->orderBy('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'current_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
