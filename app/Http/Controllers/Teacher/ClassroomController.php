<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LearningClass;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function show(LearningClass $learningClass): Response
    {
        $learningClass->loadMissing(['cohort', 'learners.childProfile']);

        Gate::authorize('view', $learningClass);

        return Inertia::render('teacher/classroom', [
            'classroom' => [
                'id' => $learningClass->id,
                'name' => $learningClass->name,
                'code' => $learningClass->class_code,
                'cohort' => $learningClass->cohort?->name,
                'is_active' => $learningClass->is_active,
                'learners_count' => $learningClass->learners->count(),
            ],
            'learners' => $learningClass->learners->map(static fn ($learner): array => [
                'id' => $learner->id,
                'name' => $learner->name,
                'learner_id' => $learner->childProfile?->learner_id,
            ]),
        ]);
    }
}
