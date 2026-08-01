<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TypingDifficultyProfile extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'version',
        'difficulty_order',
        'configuration',
        'status',
        'published_at',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $profile): void {
            $profile->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'version' => 'integer',
            'difficulty_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
