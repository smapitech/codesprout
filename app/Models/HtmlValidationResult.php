<?php

namespace App\Models;

use App\Enums\HtmlValidationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HtmlValidationResult extends Model
{
    protected $fillable = ['uuid', 'html_attempt_id', 'project_revision_id', 'validity_status', 'required_rule_count', 'satisfied_rule_count', 'unsafe_item_count', 'syntax_issue_count', 'structure_issue_count', 'accessibility_issue_count', 'assistance_summary', 'result_summary', 'calculation_version', 'result_checksum', 'completed_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $result) => $result->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'validity_status' => HtmlValidationStatus::class,
            'assistance_summary' => 'array',
            'result_summary' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(HtmlAttempt::class, 'html_attempt_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ProjectRevision::class, 'project_revision_id');
    }

    public function requirementResults(): HasMany
    {
        return $this->hasMany(HtmlRequirementResult::class);
    }
}
