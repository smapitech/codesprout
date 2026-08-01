<?php

namespace App\Models;

use App\Enums\MasteryLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillProgressRecord extends Model
{
    protected $fillable = [
        'child_id',
        'skill_id',
        'skill_slug',
        'curriculum_context',
        'current_mastery',
        'highest_mastery',
        'mastery_label',
        'attempts_count',
        'completed_activities_count',
        'evidence_count',
        'last_evidence_at',
        'calculated_at',
        'evidence_summary',
    ];

    protected function casts(): array
    {
        return [
            'current_mastery' => 'integer',
            'highest_mastery' => 'integer',
            'mastery_label' => MasteryLabel::class,
            'attempts_count' => 'integer',
            'completed_activities_count' => 'integer',
            'evidence_count' => 'integer',
            'last_evidence_at' => 'datetime',
            'calculated_at' => 'datetime',
            'evidence_summary' => 'array',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
