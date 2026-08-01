<?php

namespace App\Models;

use Database\Factories\SubmissionAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAttachment extends Model
{
    /** @use HasFactory<SubmissionAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_attempt_id',
        'assignment_item_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssignmentAttempt::class, 'assignment_attempt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AssignmentItem::class, 'assignment_item_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
