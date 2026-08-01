<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProjectTemplate extends Model
{
    protected $fillable = ['uuid', 'slug', 'title', 'description', 'status', 'current_version_id', 'created_by', 'updated_by', 'archived_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $template) => $template->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['status' => ContentStatus::class, 'archived_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProjectTemplateVersion::class)->orderBy('version_number');
    }
}
