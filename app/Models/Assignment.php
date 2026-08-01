<?php

namespace App\Models;

use App\Enums\AssignmentType;
use App\Enums\ContentStatus;
use App\Models\Concerns\HasContentStatus;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasContentStatus, HasFactory;

    protected $fillable = [
        'owner_id',
        'created_by',
        'assignment_type',
        'status',
        'current_version_id',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'assignment_type' => AssignmentType::class,
            'status' => ContentStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AssignmentVersion::class)->orderBy('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(AssignmentVersion::class, 'current_version_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
