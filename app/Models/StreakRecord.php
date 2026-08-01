<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreakRecord extends Model
{
    protected $fillable = [
        'child_id',
        'learning_date',
        'qualifying_activity_count',
        'first_qualifying_activity_at',
        'last_qualifying_activity_at',
        'timezone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'learning_date' => 'date',
            'qualifying_activity_count' => 'integer',
            'first_qualifying_activity_at' => 'datetime',
            'last_qualifying_activity_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
