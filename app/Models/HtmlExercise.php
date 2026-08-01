<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\HtmlExerciseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HtmlExercise extends Model
{
    protected $fillable = ['uuid', 'slug', 'title', 'exercise_type', 'description', 'child_instructions', 'teacher_instructions', 'status', 'current_version_id', 'created_by', 'updated_by', 'archived_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $exercise) => $exercise->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'exercise_type' => HtmlExerciseType::class,
            'status' => ContentStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(HtmlExerciseVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(HtmlExerciseVersion::class)->orderBy('version_number');
    }
}
