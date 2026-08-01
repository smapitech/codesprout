<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAutosave extends Model
{
    protected $fillable = ['learner_webpage_project_id', 'autosave_uuid', 'based_on_state_version', 'resulting_state_version', 'bounded_source', 'source_checksum', 'client_instance_id', 'saved_at', 'expires_at'];

    protected function casts(): array
    {
        return ['saved_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(LearnerWebpageProject::class, 'learner_webpage_project_id');
    }
}
