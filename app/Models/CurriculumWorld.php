<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\CurriculumWorldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumWorld extends Model
{
    /** @use HasFactory<CurriculumWorldFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'curriculum_id',
        'name',
        'slug',
        'world_number',
        'short_description',
        'story_description',
        'learning_outcomes',
        'theme_colour',
        'accent_colour',
        'icon_path',
        'cover_image_path',
        'estimated_weeks',
        'display_order',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'world_number' => 'integer',
            'learning_outcomes' => 'array',
            'estimated_weeks' => 'integer',
            'display_order' => 'integer',
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(CurriculumUnit::class, 'world_id');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'curriculum_world_prerequisites',
            'curriculum_world_id',
            'prerequisite_world_id',
        )->withTimestamps();
    }

    public function dependentWorlds(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'curriculum_world_prerequisites',
            'prerequisite_world_id',
            'curriculum_world_id',
        )->withTimestamps();
    }
}
