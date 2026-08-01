<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\TypingExerciseType;
use App\Enums\TypingInputMethod;
use App\Enums\TypingSessionStatus;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\AuditLog;
use App\Models\LessonStage;
use App\Models\Skill;
use App\Models\TypingDifficultyProfile;
use App\Models\TypingEventBatch;
use App\Models\TypingExercise;
use App\Models\TypingExerciseVersion;
use App\Models\TypingInputEvent;
use App\Models\TypingResult;
use App\Models\TypingSession;
use App\Models\User;
use App\Services\Typing\TypingExercisePublicationService;
use App\Services\Typing\TypingSessionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TypingSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearExistingTypingData();

        $admin = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
        $teacher = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
        $children = User::role('child')->orderBy('name')->get();
        $child = $children->firstOrFail();
        $otherChild = $children->skip(1)->first() ?? $child;
        $stage = LessonStage::query()->published()->first();
        $profiles = $this->seedDifficultyProfiles($admin);
        $versions = $this->seedExercises($admin, $profiles);
        $service = app(TypingSessionService::class);

        $this->seedSessionExamples($service, $versions, $child, $otherChild, $teacher, $admin, $stage);
        $this->linkTypingAssignment($service, $versions['first-three-letter-words'], $child, $stage);

        AuditLog::query()->create([
            'actor_user_id' => $admin->id,
            'action' => 'typing.seeded.phase_six',
            'subject_type' => TypingExercise::class,
            'subject_id' => TypingExercise::query()->first()?->id,
            'metadata' => [
                'profiles' => TypingDifficultyProfile::query()->count(),
                'exercises' => TypingExercise::query()->count(),
                'sessions' => TypingSession::query()->count(),
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, TypingDifficultyProfile>
     */
    private function seedDifficultyProfiles(User $admin): array
    {
        $rows = [
            ['Key Explorer', 1, ['prompt_length' => 'single_key', 'timer' => false, 'assistance' => 'high', 'onscreen_keyboard' => true]],
            ['Letter Starter', 2, ['prompt_length' => 'short', 'timer' => false, 'assistance' => 'supported', 'backspace' => true]],
            ['Word Builder', 3, ['word_length' => '2-4', 'timer' => false, 'images' => true]],
            ['Capital Adventurer', 4, ['capitalisation' => 'introductory', 'punctuation' => 'basic']],
            ['Sentence Grower', 5, ['sentence_length' => 'short', 'timer' => 'calm_optional']],
            ['Accuracy Champion', 6, ['target_accuracy' => [80, 95], 'key_highlighting' => 'reduced']],
            ['Typing Trailblazer', 7, ['mixed_content' => true, 'assessment_ready' => true]],
        ];

        $profiles = [];
        foreach ($rows as [$name, $order, $configuration]) {
            $profiles[Str::slug($name)] = TypingDifficultyProfile::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'version' => 1,
                'difficulty_order' => $order,
                'configuration' => $configuration,
                'status' => ContentStatus::Published,
                'published_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        return $profiles;
    }

    /**
     * @param  array<string, TypingDifficultyProfile>  $profiles
     * @return array<string, TypingExerciseVersion>
     */
    private function seedExercises(User $admin, array $profiles): array
    {
        $publication = app(TypingExercisePublicationService::class);
        $blueprints = [
            ['Find the Letter', TypingExerciseType::KeyDiscovery, 'key-explorer', [['Find the letter A.', 'a', ['a']], ['Find the Spacebar.', ' ', ['spacebar']]]],
            ['Vowel Adventure', TypingExerciseType::LetterPractice, 'letter-starter', [['Type a.', 'a', ['a']], ['Type e.', 'e', ['e']]]],
            ['Alphabet Steps', TypingExerciseType::LetterSequence, 'letter-starter', [['Type abc.', 'abc', ['a', 'b', 'c']]]],
            ['First Three-Letter Words', TypingExerciseType::WordTyping, 'word-builder', [['Type cat.', 'cat', ['c', 'a', 't']], ['Type sun.', 'sun', ['s', 'u', 'n']]]],
            ['Animal Words', TypingExerciseType::WordTyping, 'word-builder', [['Type dog.', 'dog', ['d', 'o', 'g']], ['Type hen.', 'hen', ['h', 'e', 'n']]]],
            ['Colour Words', TypingExerciseType::WordTyping, 'word-builder', [['Type red.', 'red', ['r', 'e', 'd']], ['Type blue.', 'blue', ['b', 'l', 'u', 'e']]]],
            ['Computer Words', TypingExerciseType::WordTyping, 'word-builder', [['Type key.', 'key', ['k', 'e', 'y']], ['Type mouse.', 'mouse', ['m', 'o', 'u', 's', 'e']]]],
            ['Spacebar Practice', TypingExerciseType::SpecialKey, 'key-explorer', [['Type hi there.', 'hi there', ['h', 'i', 'spacebar', 't']]]],
            ['Enter-Key Practice', TypingExerciseType::SpecialKey, 'key-explorer', [['Press Enter after go.', "go\n", ['g', 'o', 'enter']]]],
            ['Backspace and Correction', TypingExerciseType::LetterPractice, 'letter-starter', [['Type map and fix carefully.', 'map', ['m', 'a', 'p']]]],
            ['Capital-Letter Practice', TypingExerciseType::CapitalLetter, 'capital-adventurer', [['Type Ada.', 'Ada', ['shift', 'a', 'd']]]],
            ['Number-Row Practice', TypingExerciseType::NumberPractice, 'typing-trailblazer', [['Type 123.', '123', ['1', '2', '3']]]],
            ['Arrow-Key Practice', TypingExerciseType::SpecialKey, 'key-explorer', [['Press right arrow.', 'ArrowRight', ['arrowright']]]],
            ['Punctuation Starter', TypingExerciseType::Punctuation, 'capital-adventurer', [['Type I can type.', 'I can type.', ['shift', 'i', 'spacebar', '.']]]],
            ['Short Sentence Practice', TypingExerciseType::SentenceTyping, 'sentence-grower', [['Type the sentence.', 'I can type.', ['i', 'spacebar', 'c', '.']]]],
            ['Listen and Type', TypingExerciseType::ListenAndType, 'word-builder', [['Listen, then type sun.', 'sun', ['s', 'u', 'n']]]],
            ['Accuracy Builder', TypingExerciseType::TimedPractice, 'accuracy-champion', [['Type red cat.', 'red cat', ['r', 'e', 'd', 'spacebar', 'c']]]],
            ['Calm Timed Practice', TypingExerciseType::TimedPractice, 'typing-trailblazer', [['Type I am ready.', 'I am ready.', ['shift', 'i', 'spacebar', '.']]]],
        ];

        $versions = [];
        $typingSkill = Skill::query()->where('slug', 'typing-accuracy')->orWhere('name', 'like', '%typing%')->first();

        foreach ($blueprints as [$title, $type, $profileSlug, $items]) {
            $exercise = $publication->createDraft([
                'title' => $title,
                'exercise_type' => $type->value,
                'description' => "Seeded {$type->label()} collection for CodeSprout development.",
                'child_instructions' => 'Take your time. Look for each key and type carefully.',
                'teacher_instructions' => 'Use this exercise for short, encouraging practice.',
                'typing_difficulty_profile_id' => $profiles[$profileSlug]->id,
                'content_configuration' => [
                    'minimum_items' => 1,
                    'maximum_items' => count($items),
                    'feedback_message' => 'Your careful typing is growing.',
                    'case_sensitive' => in_array($type, [TypingExerciseType::CapitalLetter, TypingExerciseType::SentenceTyping], true),
                ],
                'case_sensitive' => in_array($type, [TypingExerciseType::CapitalLetter, TypingExerciseType::SentenceTyping], true) ? 'case_sensitive' : 'case_insensitive',
                'completion_criteria' => ['minimum_items' => 1, 'minimum_accuracy' => 60, 'allow_pause' => ! in_array($type, [TypingExerciseType::TypingAssessment], true)],
                'accuracy_requirement' => 60,
                'items' => array_map(fn (array $row, int $index): array => [
                    'prompt_text' => $row[0],
                    'expected_text' => $row[1],
                    'target_keys' => $row[2],
                    'display_order' => $index + 1,
                ], $items, array_keys($items)),
                'skill_ids' => $typingSkill ? [$typingSkill->id] : [],
            ], $admin);

            $versions[$exercise->slug] = $publication->publish($exercise->currentVersion, $admin);
        }

        $draft = $publication->createDraft([
            'title' => 'Draft Home Row Garden',
            'exercise_type' => TypingExerciseType::LetterSequence->value,
            'child_instructions' => 'Draft only.',
            'content_configuration' => ['minimum_items' => 1],
            'items' => [['prompt_text' => 'Draft asdf.', 'expected_text' => 'asdf', 'target_keys' => ['a', 's', 'd', 'f']]],
        ], $admin);
        $draft->touch();

        $archived = $publication->createDraft([
            'title' => 'Archived Typing Path',
            'exercise_type' => TypingExerciseType::WordTyping->value,
            'child_instructions' => 'Historical practice.',
            'content_configuration' => ['minimum_items' => 1],
            'items' => [['prompt_text' => 'Type old.', 'expected_text' => 'old', 'target_keys' => ['o', 'l', 'd']]],
        ], $admin);
        $publication->publish($archived->currentVersion, $admin);
        $publication->archive($archived, $admin);

        return $versions;
    }

    private function seedSessionExamples(TypingSessionService $service, array $versions, User $child, User $otherChild, User $teacher, User $admin, ?LessonStage $stage): void
    {
        $active = $service->start($versions['find-the-letter'], $child, ['lesson_stage_id' => $stage?->id, 'client_session_identifier' => 'typing-seed-active']);
        $this->recordText($service, $active, $child, 'a', 'a', false);

        $paused = $service->start($versions['vowel-adventure'], $child, ['client_session_identifier' => 'typing-seed-paused']);
        $service->pause($paused, $child);

        $resumed = $service->resume($paused->fresh(), $child);
        $resumed->forceFill(['status' => TypingSessionStatus::Resumed])->save();

        $completed = $service->start($versions['first-three-letter-words'], $child, ['lesson_stage_id' => $stage?->id, 'client_session_identifier' => 'typing-seed-completed']);
        $this->recordText($service, $completed, $child, "cat\nsun", "cat\nsun");
        $service->complete($completed->fresh(), $child, 'typing-seed-completed');

        $submitted = $service->start($versions['short-sentence-practice'], $otherChild, ['client_session_identifier' => 'typing-seed-submitted']);
        $this->recordText($service, $submitted, $otherChild, 'I can type.', 'I can type.');
        $submitted = $service->complete($submitted->fresh(), $otherChild, 'typing-seed-submitted');
        $submitted->forceFill(['status' => TypingSessionStatus::Submitted, 'submitted_at' => now()])->save();

        $review = $service->start($versions['accuracy-builder'], $child, ['client_session_identifier' => 'typing-seed-review']);
        $this->recordText($service, $review, $child, 'red cat', 'red cat', true);
        $service->complete($review->fresh(), $child, 'typing-seed-review');

        TypingSession::query()->create([
            'child_id' => $child->id,
            'typing_exercise_version_id' => $versions['computer-words']->id,
            'session_type' => 'practice',
            'input_method' => TypingInputMethod::Unknown,
            'keyboard_layout' => 'qwerty',
            'status' => TypingSessionStatus::Abandoned,
            'started_at' => now()->subHours(2),
            'abandoned_at' => now()->subHour(),
            'expires_at' => now()->subMinutes(30),
            'state' => [],
        ]);

        TypingSession::query()->create([
            'child_id' => $child->id,
            'typing_exercise_version_id' => $versions['animal-words']->id,
            'session_type' => 'practice',
            'input_method' => TypingInputMethod::Unknown,
            'keyboard_layout' => 'qwerty',
            'status' => TypingSessionStatus::Expired,
            'started_at' => now()->subHours(3),
            'expires_at' => now()->subHour(),
            'state' => [],
        ]);

        $service->preview($versions['capital-letter-practice'], $teacher, 'teacher_preview');
        $service->preview($versions['number-row-practice'], $admin, 'administrator_preview');
    }

    private function linkTypingAssignment(TypingSessionService $service, $version, User $child, ?LessonStage $stage): void
    {
        $allocation = AssignmentAllocation::query()
            ->with('assignmentVersion.items')
            ->whereHas('classroom.learners', fn ($query) => $query->where('users.id', $child->id))
            ->first();

        $attempt = $allocation
            ? AssignmentAttempt::query()->where('assignment_allocation_id', $allocation->id)->where('child_id', $child->id)->first()
            : null;

        $item = $allocation?->assignmentVersion->items->first();
        if (! $allocation || ! $attempt || ! $item instanceof AssignmentItem) {
            return;
        }

        $item->forceFill(['typing_exercise_version_id' => $version->id])->save();
        $session = $service->start($version, $child, [
            'assignment_allocation_id' => $allocation->id,
            'assignment_attempt_id' => $attempt->id,
            'assignment_item_id' => $item->id,
            'lesson_stage_id' => $stage?->id,
            'session_type' => 'assignment',
            'client_session_identifier' => 'typing-seed-assignment',
        ]);
        $this->recordText($service, $session, $child, "cat\nsun", "cat\nsun");
        $service->complete($session->fresh(), $child, 'typing-seed-assignment');
    }

    private function recordText(TypingSessionService $service, TypingSession $session, User $actor, string $expected, string $entered, bool $paste = false): void
    {
        $events = [];
        $sequence = 1;

        if ($paste) {
            $events[] = [
                'sequence_number' => $sequence,
                'typing_content_item_id' => $session->exerciseVersion->contentItems->first()?->id,
                'character_position' => 0,
                'event_type' => 'paste',
                'expected_character' => mb_substr($expected, 0, 1),
                'entered_character' => $entered,
                'correctness_state' => 'assistance',
                'input_method' => TypingInputMethod::AssistiveInput->value,
                'elapsed_offset_ms' => 30_000,
            ];
        } else {
            foreach (preg_split('//u', $entered, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $position => $char) {
                $events[] = [
                    'sequence_number' => $sequence++,
                    'typing_content_item_id' => $session->exerciseVersion->contentItems->first()?->id,
                    'character_position' => $position,
                    'event_type' => 'input',
                    'expected_character' => mb_substr($expected, $position, 1),
                    'entered_character' => $char,
                    'correctness_state' => $char === mb_substr($expected, $position, 1) ? 'correct' : 'incorrect',
                    'input_method' => TypingInputMethod::PhysicalKeyboard->value,
                    'elapsed_offset_ms' => 20_000 + ($position * 1200),
                ];
            }
        }

        $service->recordBatch($session->fresh(['exerciseVersion.exercise', 'exerciseVersion.contentItems']), $actor, [
            'batch_uuid' => (string) Str::uuid(),
            'events' => $events,
        ]);
    }

    private function clearExistingTypingData(): void
    {
        if (Schema::hasColumn('assignment_items', 'typing_exercise_version_id')) {
            AssignmentItem::query()->whereNotNull('typing_exercise_version_id')->update(['typing_exercise_version_id' => null]);
        }

        TypingResult::query()->delete();
        TypingInputEvent::query()->delete();
        TypingEventBatch::query()->delete();
        TypingSession::query()->delete();
        DB::table('typing_exercise_skill')->delete();
        DB::table('typing_exercises')->update(['current_version_id' => null]);
        DB::table('typing_content_items')->delete();
        DB::table('typing_exercise_versions')->delete();
        DB::table('typing_exercises')->delete();
        DB::table('typing_difficulty_profiles')->delete();
    }
}
