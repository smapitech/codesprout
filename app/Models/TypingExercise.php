<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TypingExerciseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TypingExercise extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'exercise_type',
        'description',
        'child_instructions',
        'teacher_instructions',
        'status',
        'current_version_id',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $exercise): void {
            $exercise->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'exercise_type' => TypingExerciseType::class,
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
        return $this->hasMany(TypingExerciseVersion::class)->orderBy('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(TypingExerciseVersion::class, 'current_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
