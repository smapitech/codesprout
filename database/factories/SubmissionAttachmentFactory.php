<?php

namespace Database\Factories;

use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\SubmissionAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAttachment>
 */
class SubmissionAttachmentFactory extends Factory
{
    protected $model = SubmissionAttachment::class;

    public function definition(): array
    {
        return [
            'assignment_attempt_id' => AssignmentAttempt::factory(),
            'assignment_item_id' => AssignmentItem::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'assignments/example.txt',
            'original_name' => 'example.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 128,
        ];
    }
}
