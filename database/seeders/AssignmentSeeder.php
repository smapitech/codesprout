<?php

namespace Database\Seeders;

use App\Enums\AssignmentFeedbackMode;
use App\Enums\AssignmentFeedbackType;
use App\Enums\AssignmentScoringMethod;
use App\Enums\AssignmentType;
use App\Enums\QuestionType;
use App\Models\AssessmentRubric;
use App\Models\AssessmentRubricCriterion;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentFeedback;
use App\Models\AssignmentResponse;
use App\Models\AssignmentVersion;
use App\Models\LearnerGroup;
use App\Models\LearnerGroupMember;
use App\Models\LearningClass;
use App\Models\Skill;
use App\Models\SubmissionAttachment;
use App\Models\User;
use App\Services\Assignments\AssignmentAllocationService;
use App\Services\Assignments\AssignmentAttemptService;
use App\Services\Assignments\AssignmentPublicationService;
use App\Services\Assignments\AssignmentVersionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->clearExistingAssignmentData();

            $admin = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
            $teacher = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
            $children = User::role('child')->with('childProfile')->orderBy('name')->get();
            $childOne = $children->firstOrFail();
            $childTwo = $children->skip(1)->first() ?? $childOne;
            $class = LearningClass::query()->where('class_code', 'CB-KEY-01')->firstOrFail();

            $group = LearnerGroup::query()->create([
                'class_id' => $class->id,
                'name' => 'Sprout Explorers',
                'description' => 'Sample small group for keyboard and symbol practice.',
                'created_by' => $teacher->id,
            ]);

            foreach ([$childOne, $childTwo] as $child) {
                LearnerGroupMember::query()->create([
                    'learner_group_id' => $group->id,
                    'child_id' => $child->id,
                ]);
            }

            $versions = collect([
                $this->publishAssignment($teacher, 'Mouse Click Choices', AssignmentType::Practice, QuestionType::MultipleChoice, [
                    'prompt' => 'Which action opens a button?',
                    'options' => [
                        ['Click', 'click', true],
                        ['Look away', 'look_away', false],
                    ],
                ]),
                $this->publishAssignment($teacher, 'Match Computer Helpers', AssignmentType::Mission, QuestionType::MatchItems, [
                    'prompt' => 'Match each computer helper to what it does.',
                    'options' => [
                        ['Mouse', 'mouse', 'Click and point'],
                        ['Keyboard', 'keyboard', 'Type letters'],
                    ],
                ]),
                $this->publishAssignment($teacher, 'Find the Enter Key', AssignmentType::Practice, QuestionType::PressRequestedKey, [
                    'prompt' => 'Choose the Enter key.',
                    'options' => [
                        ['Enter', 'enter', true],
                        ['Spacebar', 'spacebar', false],
                    ],
                ]),
                $this->publishAssignment($teacher, 'Type Ada Carefully', AssignmentType::Practice, QuestionType::TypeWord, [
                    'prompt' => 'Type the name Ada with a capital A.',
                    'accepted_answers' => ['Ada'],
                    'case_sensitive' => true,
                ]),
                $this->publishAssignment($teacher, 'Symbol Mountain Match', AssignmentType::Mission, QuestionType::MatchSymbolToName, [
                    'prompt' => 'Choose the name for <.',
                    'options' => [
                        ['Less-than symbol', 'less_than', true],
                        ['Slash symbol', 'slash', false],
                    ],
                ]),
                $this->publishAssignment($teacher, 'Build an H1 Tag', AssignmentType::Practice, QuestionType::BuildHtmlTag, [
                    'prompt' => 'Type the HTML heading tag.',
                    'accepted_answers' => ['<h1>'],
                    'case_sensitive' => false,
                    'require_punctuation' => true,
                ]),
                $this->publishAssignment($teacher, 'Order a Webpage', AssignmentType::Assessment, QuestionType::ArrangeCodeIntoCorrectOrder, [
                    'prompt' => 'Put the page pieces in the right order.',
                    'options' => [
                        ['Open heading', '<h1>'],
                        ['Words', 'Hello'],
                        ['Close heading', '</h1>'],
                    ],
                ]),
                $this->publishAssignment($teacher, 'My First Name Card', AssignmentType::Project, QuestionType::CreativeProject, [
                    'prompt' => 'Tell your teacher what you want on your name card.',
                ]),
                $this->publishAssignment($teacher, 'Teacher Observation: Mouse Grip', AssignmentType::Observation, QuestionType::TeacherObservation, [
                    'prompt' => 'Teacher observes pointing, clicking and relaxed hand position.',
                ]),
            ]);

            $allocationService = app(AssignmentAllocationService::class);
            $attemptService = app(AssignmentAttemptService::class);
            $rubricCriterion = $this->seedCreativeProjectRubric($teacher);

            $classAllocation = $allocationService->createAllocation($versions[0], [
                'class_id' => $class->id,
                'available_from' => now()->subDay(),
                'due_at' => now()->addDays(4),
                'attempt_limit' => 2,
            ], $teacher);

            $groupAllocation = $allocationService->createAllocation($versions[1], [
                'group_id' => $group->id,
                'available_from' => now()->subDay(),
                'due_at' => now()->addDays(3),
                'attempt_limit' => 2,
            ], $teacher);

            $childAllocation = $allocationService->createAllocation($versions[7], [
                'child_id' => $childOne->id,
                'available_from' => now()->subHours(2),
                'due_at' => now()->addWeek(),
                'attempt_limit' => 2,
            ], $teacher);

            $allocationService->createAllocation($versions[2], [
                'class_id' => $class->id,
                'available_from' => now()->addDays(2),
                'due_at' => now()->addDays(8),
                'attempt_limit' => 1,
            ], $teacher);

            $inProgress = $attemptService->startAttempt($classAllocation, $childOne);
            $attemptService->saveResponse($inProgress, $versions[0]->items->first(), ['selected_option_value' => 'click'], $childOne);

            $submitted = $attemptService->startAttempt($groupAllocation, $childOne);
            $attemptService->saveResponse($submitted, $versions[1]->items->first(), [
                'pairs' => [
                    'mouse' => 'Click and point',
                    'keyboard' => 'Type letters',
                ],
            ], $childOne);
            $attemptService->submitAttempt($submitted, $childOne);

            $marked = $attemptService->startAttempt($childAllocation, $childOne);
            $attemptService->saveResponse($marked, $versions[7]->items->first(), ['text' => 'A yellow card with my name and a star.'], $childOne);
            $attemptService->submitAttempt($marked, $childOne);
            $attemptService->markAttempt($marked, $teacher, [
                'manual_scores' => [$versions[7]->items->first()->id => 3],
                'rubric_scores' => [$rubricCriterion->id => 99],
                'feedback_text' => 'Lovely idea. Your name card plan is clear and colourful.',
                'feedback_type' => AssignmentFeedbackType::Achievement->value,
                'visible_to_child' => true,
                'visible_to_parent' => true,
            ]);

            $this->seedExtraAllocations($versions, $class, $teacher);
            $this->seedAdminOwnedLibraryItem($admin);
        });
    }

    private function publishAssignment(User $teacher, string $title, AssignmentType $assignmentType, QuestionType $questionType, array $config): AssignmentVersion
    {
        $payload = [
            'assignment_type' => $assignmentType->value,
            'title' => $title,
            'short_description' => $config['prompt'],
            'child_instructions' => 'Listen, look carefully and try your best.',
            'teacher_instructions' => 'Support children with spoken prompts where useful.',
            'estimated_minutes' => 8,
            'difficulty_level' => 'introductory',
            'default_attempt_limit' => 2,
            'feedback_mode' => AssignmentFeedbackMode::AfterSubmission->value,
            'scoring_method' => AssignmentScoringMethod::LatestAttempt->value,
            'items' => [$this->itemPayload($title, $questionType, $config)],
            'skill_ids' => Skill::query()->published()->limit(2)->pluck('id')->all(),
            'curriculum_links' => [],
        ];

        $version = app(AssignmentVersionService::class)->createDraft($payload, $teacher);

        return app(AssignmentPublicationService::class)->publishVersion($version, $teacher);
    }

    private function itemPayload(string $title, QuestionType $questionType, array $config): array
    {
        $base = [
            'title' => $title,
            'prompt_text' => $config['prompt'],
            'question_type' => $questionType->value,
            'interaction_type' => $questionType->interactionType()->value,
            'points' => 3,
            'is_required' => true,
            'hint_text' => 'Take your time and look for the clue.',
            'display_order' => 1,
            'configuration' => [],
            'grading_configuration' => [],
            'options' => [],
        ];

        if (in_array($questionType, [QuestionType::MatchItems, QuestionType::MatchOpeningAndClosingHtmlTags], true)) {
            $base['options'] = collect($config['options'])->map(fn (array $option, int $index): array => [
                'option_text' => $option[0],
                'option_value' => $option[1],
                'matching_key' => $option[2],
                'is_correct' => true,
                'display_order' => $index + 1,
            ])->all();

            return $base;
        }

        if (in_array($questionType, [QuestionType::OrderSequence, QuestionType::ArrangeCodeIntoCorrectOrder], true)) {
            $base['options'] = collect($config['options'])->map(fn (array $option, int $index): array => [
                'option_text' => $option[0],
                'option_value' => $option[1],
                'is_correct' => true,
                'display_order' => $index + 1,
            ])->all();

            return $base;
        }

        if (! empty($config['accepted_answers'])) {
            $base['grading_configuration'] = [
                'accepted_answers' => $config['accepted_answers'],
                'trim_spaces' => true,
                'case_sensitive' => (bool) ($config['case_sensitive'] ?? false),
                'require_punctuation' => (bool) ($config['require_punctuation'] ?? false),
                'placeholder' => 'Type here',
            ];

            return $base;
        }

        if (! in_array($questionType, [QuestionType::CreativeProject, QuestionType::TeacherObservation, QuestionType::ShortChildResponse], true)) {
            $base['options'] = collect($config['options'])->map(fn (array $option, int $index): array => [
                'option_text' => $option[0],
                'option_value' => $option[1],
                'is_correct' => $option[2],
                'display_order' => $index + 1,
            ])->all();
        }

        return $base;
    }

    private function seedExtraAllocations($versions, LearningClass $class, User $teacher): void
    {
        $allocationService = app(AssignmentAllocationService::class);

        foreach ($versions->slice(3, 3) as $version) {
            $allocationService->createAllocation($version, [
                'class_id' => $class->id,
                'available_from' => now()->subHours(3),
                'due_at' => now()->addDays(6),
                'attempt_limit' => 2,
            ], $teacher);
        }
    }

    private function seedAdminOwnedLibraryItem(User $admin): void
    {
        $this->publishAssignment($admin, 'Library True or False Starter', AssignmentType::Library, QuestionType::TrueFalse, [
            'prompt' => 'A keyboard helps us type letters.',
            'options' => [
                ['True', 'true', true],
                ['False', 'false', false],
            ],
        ]);
    }

    private function seedCreativeProjectRubric(User $teacher): AssessmentRubricCriterion
    {
        $rubric = AssessmentRubric::query()->create([
            'title' => 'Young Creator Project Rubric',
            'description' => 'A simple fictional rubric for checking planning, effort and child-friendly explanation.',
            'created_by' => $teacher->id,
        ]);

        return AssessmentRubricCriterion::query()->create([
            'assessment_rubric_id' => $rubric->id,
            'title' => 'Clear project idea',
            'description' => 'The child can describe what they want to create with support.',
            'maximum_points' => 3,
            'display_order' => 1,
        ]);
    }

    private function clearExistingAssignmentData(): void
    {
        SubmissionAttachment::query()->delete();
        DB::table('assignment_rubric_scores')->delete();
        DB::table('assessment_rubric_criteria')->delete();
        DB::table('assessment_rubrics')->delete();
        AssignmentFeedback::query()->delete();
        AssignmentResponse::query()->delete();
        AssignmentAttempt::query()->delete();
        AssignmentAllocation::query()->delete();
        LearnerGroupMember::query()->delete();
        LearnerGroup::query()->delete();
        DB::table('assignment_skill')->delete();
        DB::table('assignment_curriculum_links')->delete();
        DB::table('assignment_item_options')->delete();
        DB::table('assignment_items')->delete();
        DB::table('assignments')->update(['current_version_id' => null]);
        DB::table('assignment_versions')->delete();
        DB::table('assignments')->delete();
    }
}
