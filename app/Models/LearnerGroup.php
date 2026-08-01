<?php

namespace App\Models;

use Database\Factories\LearnerGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearnerGroup extends Model
{
    /** @use HasFactory<LearnerGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'class_id',
        'name',
        'description',
        'created_by',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(LearningClass::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(LearnerGroupMember::class);
    }
}
