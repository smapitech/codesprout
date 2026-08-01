<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\CurriculumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    /** @use HasFactory<CurriculumFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'target_min_age',
        'target_max_age',
        'duration_weeks',
        'lessons_per_week',
        'version',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_min_age' => 'integer',
            'target_max_age' => 'integer',
            'duration_weeks' => 'integer',
            'lessons_per_week' => 'integer',
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function worlds(): HasMany
    {
        return $this->hasMany(CurriculumWorld::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
