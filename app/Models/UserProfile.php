<?php

namespace App\Models;

use App\Support\AvatarUrl;
use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'preferred_name',
        'date_of_birth',
        'avatar_path',
        'notes',
    ];

    protected $appends = [
        'age',
        'display_name',
        'full_name',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->last_name])->filter()->implode(' '));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferred_name ?: $this->first_name ?: $this->full_name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return AvatarUrl::resolve($this->avatar_path);
    }
}
