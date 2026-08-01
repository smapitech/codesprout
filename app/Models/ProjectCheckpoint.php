<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCheckpoint extends Model
{
    protected $fillable = ['learner_webpage_project_id', 'project_revision_id', 'checkpoint_identifier', 'status', 'html_validation_result_id', 'completed_at', 'progress_event_id'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearnerWebpageProject::class, 'learner_webpage_project_id');
    }
}
