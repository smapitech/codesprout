<?php

namespace App\Models;

use Database\Factories\LearnerGroupMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerGroupMember extends Model
{
    /** @use HasFactory<LearnerGroupMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'learner_group_id',
        'child_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LearnerGroup::class, 'learner_group_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
