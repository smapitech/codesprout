<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LearnerLevel extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'level_number',
        'xp_threshold',
        'description',
        'status',
        'version',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'level_number' => 'integer',
            'xp_threshold' => 'integer',
            'status' => ContentStatus::class,
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $level): void {
            $level->uuid ??= (string) Str::uuid();
        });
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
