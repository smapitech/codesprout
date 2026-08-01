<?php

namespace App\Models;

use Database\Factories\ChildProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildProfile extends Model
{
    /** @use HasFactory<ChildProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'learner_id',
        'pin_mode',
        'pin_hash',
        'pin_hint',
        'last_pin_verified_at',
        'pin_reset_required_at',
        'notes',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_pin_verified_at' => 'datetime',
            'pin_reset_required_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
