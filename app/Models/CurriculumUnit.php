<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\CurriculumUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumUnit extends Model
{
    /** @use HasFactory<CurriculumUnitFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'world_id',
        'title',
        'slug',
        'week_number',
        'description',
        'learning_outcomes',
        'display_order',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'week_number' => 'integer',
            'learning_outcomes' => 'array',
            'display_order' => 'integer',
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(CurriculumWorld::class, 'world_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CurriculumLesson::class, 'unit_id');
    }
}
