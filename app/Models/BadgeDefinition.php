<?php

namespace App\Models;

use App\Enums\BadgeCategory;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BadgeDefinition extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'short_description',
        'long_description',
        'badge_category',
        'badge_image_path',
        'alt_text',
        'qualification_type',
        'qualification_configuration',
        'display_order',
        'emphasis',
        'repeatable',
        'maximum_awards',
        'status',
        'published_at',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'badge_category' => BadgeCategory::class,
            'qualification_configuration' => 'array',
            'display_order' => 'integer',
            'repeatable' => 'boolean',
            'maximum_awards' => 'integer',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $badge): void {
            $badge->uuid ??= (string) Str::uuid();
        });
    }

    public function awards(): HasMany
    {
        return $this->hasMany(BadgeAward::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
