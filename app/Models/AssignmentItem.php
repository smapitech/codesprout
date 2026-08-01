<?php

namespace App\Models;

use App\Enums\InteractionType;
use App\Enums\QuestionType;
use Database\Factories\AssignmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentItem extends Model
{
    /** @use HasFactory<AssignmentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_version_id',
        'game_version_id',
        'typing_exercise_version_id',
        'html_exercise_version_id',
        'project_template_version_id',
        'title',
        'prompt_text',
        'audio_prompt_path',
        'image_path',
        'question_type',
        'interaction_type',
        'points',
        'is_required',
        'hint_text',
        'hint_audio_path',
        'explanation_text',
        'display_order',
        'configuration',
        'grading_configuration',
    ];

    protected function casts(): array
    {
        return [
            'question_type' => QuestionType::class,
            'interaction_type' => InteractionType::class,
            'points' => 'integer',
            'is_required' => 'boolean',
            'display_order' => 'integer',
            'configuration' => 'array',
            'grading_configuration' => 'array',
        ];
    }

    public function assignmentVersion(): BelongsTo
    {
        return $this->belongsTo(AssignmentVersion::class);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function typingExerciseVersion(): BelongsTo
    {
        return $this->belongsTo(TypingExerciseVersion::class);
    }

    public function htmlExerciseVersion(): BelongsTo
    {
        return $this->belongsTo(HtmlExerciseVersion::class);
    }

    public function projectTemplateVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateVersion::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(AssignmentItemOption::class)->orderBy('display_order');
    }
}
