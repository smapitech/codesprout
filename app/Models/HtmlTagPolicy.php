<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HtmlTagPolicy extends Model
{
    protected $fillable = ['uuid', 'name', 'slug', 'version', 'allowed_tags', 'allowed_attributes', 'allowed_protocols', 'resource_limits', 'status', 'published_at', 'archived_at', 'created_by', 'updated_by', 'checksum'];

    protected static function booted(): void
    {
        static::creating(fn (self $policy) => $policy->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'allowed_tags' => 'array',
            'allowed_attributes' => 'array',
            'allowed_protocols' => 'array',
            'resource_limits' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
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
