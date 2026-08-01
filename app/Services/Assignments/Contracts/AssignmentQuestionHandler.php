<?php

namespace App\Services\Assignments\Contracts;

use App\Models\AssignmentItem;
use Illuminate\Validation\ValidationException;

interface AssignmentQuestionHandler
{
    /**
     * @throws ValidationException
     */
    public function validateConfiguration(AssignmentItem $item): void;

    /**
     * @throws ValidationException
     */
    public function validateResponse(AssignmentItem $item, mixed $response): void;

    /**
     * @return array<string, mixed>
     */
    public function transformForLearner(AssignmentItem $item): array;

    /**
     * @return array{is_correct: ?bool, score: float|int, maximum_score: float|int, feedback: string, manual_review: bool}
     */
    public function gradeResponse(AssignmentItem $item, mixed $response): array;

    public function requiresManualReview(AssignmentItem $item): bool;
}
