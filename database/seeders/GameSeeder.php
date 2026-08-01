<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\GameCategory;
use App\Enums\GameDifficulty;
use App\Enums\GameType;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentItem;
use App\Models\GameDefinition;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\GameVersion;
use App\Models\LessonStage;
use App\Models\User;
use App\Services\Games\GamePublicationService;
use App\Services\Games\GameSessionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->clearExistingGameData();

            $admin = User::query()->where('email', 'admin@childsbridge.test')->firstOrFail();
            $teacher = User::query()->where('email', 'teacher@childsbridge.test')->firstOrFail();
            $child = User::role('child')->with('childProfile')->orderBy('name')->firstOrFail();
            $lessonStage = LessonStage::query()->published()->first();

            $versions = collect($this->gameBlueprints())
                ->map(fn (array $blueprint): GameVersion => $this->createPublishedGame($blueprint, $admin));

            $draft = $this->createDraftGame($admin);
            $archived = $versions->last()?->definition;

            if ($archived) {
                app(GamePublicationService::class)->archive($archived, $admin);
            }

            $this->linkAssignmentGame($versions->first(), $child, $lessonStage, $teacher);

            $service = app(GameSessionService::class);
            $published = $versions->filter(fn (GameVersion $version): bool => $version->definition->isPublished())->values();

            $inProgress = $service->start($published[1], $child, GameDifficulty::ExtraSlow, [
                'lesson_stage_id' => $lessonStage?->id,
                'client_session_identifier' => 'seed-in-progress',
            ]);
            $service->recordAction($inProgress, $child, [
                'round_number' => 1,
                'response' => ['match' => 'Shows pictures and words'],
                'response_time_ms' => 2400,
                'hint_used' => true,
            ]);

            $paused = $service->start($published[2], $child, GameDifficulty::Slow, [
                'lesson_stage_id' => $lessonStage?->id,
                'client_session_identifier' => 'seed-paused',
            ]);
            $service->pause($paused, $child);

            $completed = $service->start($published[6], $child, GameDifficulty::Slow, [
                'lesson_stage_id' => $lessonStage?->id,
                'client_session_identifier' => 'seed-completed',
            ]);

            foreach ($completed->roundRecords as $round) {
                $service->recordAction($completed->fresh(['roundRecords', 'gameVersion.definition']), $child, [
                    'round_number' => $round->round_number,
                    'response' => ['key' => $round->round_data['expected']],
                    'response_time_ms' => 1600,
                ]);
            }

            $completed = $service->complete($completed->fresh(['roundRecords', 'gameVersion.definition']), $child, 'seed-complete');
            $completed->result?->forceFill(['released_to_parent' => true])->save();

            // Keeps a fictional draft record available for admin screens without publishing it.
            $draft->touch();
        });
    }

    private function createPublishedGame(array $blueprint, User $admin): GameVersion
    {
        $game = GameDefinition::query()->create([
            'slug' => Str::slug($blueprint['name']),
            'name' => $blueprint['name'],
            'category' => $blueprint['category'],
            'game_type' => $blueprint['game_type'],
            'description' => $blueprint['description'],
            'instructions' => $blueprint['instructions'],
            'status' => ContentStatus::Draft,
            'visibility' => 'platform',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $version = $game->versions()->create([
            'version_number' => 1,
            'configuration' => $blueprint['configuration'],
            'instruction_content' => ['written' => $blueprint['instructions'], 'speech_enabled' => true],
            'difficulty_configuration' => $this->difficultyConfiguration(),
            'supported_input_methods' => $blueprint['supported_input_methods'],
            'status' => ContentStatus::Draft,
        ]);

        $game->forceFill(['current_version_id' => $version->id])->save();

        return app(GamePublicationService::class)->publish($version, $admin);
    }

    private function createDraftGame(User $admin): GameDefinition
    {
        $game = GameDefinition::query()->create([
            'slug' => 'draft-sprout-lab',
            'name' => 'Draft Sprout Lab',
            'category' => GameCategory::ComputerDiscovery,
            'game_type' => GameType::ComputerPartIdentification,
            'description' => 'A draft-only computer discovery game used for access tests.',
            'instructions' => 'Find the named computer part.',
            'status' => ContentStatus::Draft,
            'visibility' => 'platform',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $version = $game->versions()->create([
            'version_number' => 1,
            'configuration' => [
                'items' => [
                    ['name' => 'Screen', 'value' => 'screen', 'purpose' => 'Shows pictures and words'],
                ],
                'round_count' => 1,
            ],
            'instruction_content' => ['written' => 'Find the named computer part.'],
            'difficulty_configuration' => $this->difficultyConfiguration(),
            'supported_input_methods' => ['mouse', 'touch', 'keyboard'],
            'status' => ContentStatus::Draft,
        ]);

        $game->forceFill(['current_version_id' => $version->id])->save();

        return $game;
    }

    private function linkAssignmentGame(GameVersion $gameVersion, User $child, ?LessonStage $lessonStage, User $teacher): void
    {
        $allocation = AssignmentAllocation::query()
            ->with('assignmentVersion.items')
            ->whereHas('classroom.learners', fn ($query) => $query->where('users.id', $child->id))
            ->first();

        if (! $allocation) {
            return;
        }

        $attempt = AssignmentAttempt::query()
            ->where('assignment_allocation_id', $allocation->id)
            ->where('child_id', $child->id)
            ->first();

        if (! $attempt) {
            return;
        }

        $item = $allocation->assignmentVersion->items->first();
        if (! $item instanceof AssignmentItem) {
            return;
        }

        $item->forceFill([
            'game_version_id' => $gameVersion->id,
            'configuration' => array_merge($item->configuration ?? [], [
                'game_completion_required' => true,
                'teacher_instructions' => 'Use the linked game result as supporting evidence.',
            ]),
        ])->save();

        $session = app(GameSessionService::class)->start($gameVersion, $child, GameDifficulty::Slow, [
            'assignment_allocation_id' => $allocation->id,
            'assignment_attempt_id' => $attempt->id,
            'assignment_item_id' => $item->id,
            'lesson_stage_id' => $lessonStage?->id,
            'client_session_identifier' => 'seed-assignment-game',
        ]);

        foreach ($session->roundRecords as $round) {
            app(GameSessionService::class)->recordAction($session->fresh(['roundRecords', 'gameVersion.definition']), $child, [
                'round_number' => $round->round_number,
                'response' => ['selected_part' => $round->round_data['expected']],
                'response_time_ms' => 1400,
            ]);
        }

        $completed = app(GameSessionService::class)->complete($session->fresh(['roundRecords', 'gameVersion.definition']), $child, 'seed-assignment-game-complete');
        $completed->result?->forceFill(['released_to_parent' => true])->save();
        $completed->assignmentAttempt?->touch();
        $teacher->touch();
    }

    private function difficultyConfiguration(): array
    {
        return [
            GameDifficulty::ExtraSlow->value => [
                'rounds' => 2,
                'target_duration_ms' => 6000,
                'movement_speed' => 'gentle',
                'hint_delay_ms' => 1500,
                'double_click_tolerance_ms' => 1400,
                'falling_speed' => 'extra_slow',
            ],
            GameDifficulty::Slow->value => [
                'rounds' => 3,
                'target_duration_ms' => 4500,
                'movement_speed' => 'slow',
                'hint_delay_ms' => 2500,
                'double_click_tolerance_ms' => 1100,
                'falling_speed' => 'slow',
            ],
            GameDifficulty::Normal->value => [
                'rounds' => 4,
                'target_duration_ms' => 3500,
                'movement_speed' => 'normal',
                'hint_delay_ms' => 3500,
                'double_click_tolerance_ms' => 900,
                'falling_speed' => 'normal',
            ],
        ];
    }

    private function gameBlueprints(): array
    {
        return [
            [
                'name' => 'Computer Part Explorer',
                'category' => GameCategory::ComputerDiscovery,
                'game_type' => GameType::ComputerPartIdentification,
                'description' => 'Children identify safe parts of a friendly computer illustration.',
                'instructions' => 'Listen for the computer part, then click or tap it.',
                'supported_input_methods' => ['mouse', 'touch', 'keyboard'],
                'configuration' => [
                    'items' => [
                        ['name' => 'Screen', 'value' => 'screen', 'purpose' => 'Shows pictures and words'],
                        ['name' => 'Keyboard', 'value' => 'keyboard', 'purpose' => 'Helps us type'],
                        ['name' => 'Mouse', 'value' => 'mouse', 'purpose' => 'Moves the pointer'],
                    ],
                    'round_count' => 3,
                ],
            ],
            [
                'name' => 'Computer Part Matching',
                'category' => GameCategory::ComputerDiscovery,
                'game_type' => GameType::ComputerPartMatching,
                'description' => 'Children match each computer part to its simple purpose.',
                'instructions' => 'Choose the purpose that matches each computer part.',
                'supported_input_methods' => ['mouse', 'touch', 'keyboard'],
                'configuration' => [
                    'items' => [
                        ['name' => 'Screen', 'value' => 'screen', 'expected' => 'Shows pictures and words'],
                        ['name' => 'Keyboard', 'value' => 'keyboard', 'expected' => 'Helps us type'],
                        ['name' => 'Speaker', 'value' => 'speaker', 'expected' => 'Plays sound'],
                    ],
                    'round_count' => 3,
                ],
            ],
            [
                'name' => 'Click the Target',
                'category' => GameCategory::MouseControl,
                'game_type' => GameType::SingleClickTarget,
                'description' => 'Children click or tap large friendly targets.',
                'instructions' => 'Click the glowing target when you see it.',
                'supported_input_methods' => ['mouse', 'touch', 'keyboard'],
                'configuration' => [
                    'targets' => [
                        ['label' => 'Star target', 'value' => 'star'],
                        ['label' => 'Sprout target', 'value' => 'sprout'],
                        ['label' => 'Flag target', 'value' => 'flag'],
                    ],
                    'round_count' => 3,
                ],
            ],
            [
                'name' => 'Double-Click Practice',
                'category' => GameCategory::MouseControl,
                'game_type' => GameType::DoubleClickPractice,
                'description' => 'Children practise two gentle clicks close together.',
                'instructions' => 'Click twice gently on the friendly folder.',
                'supported_input_methods' => ['mouse', 'touch'],
                'configuration' => [
                    'targets' => [
                        ['label' => 'Folder', 'value' => 'folder'],
                        ['label' => 'Picture', 'value' => 'picture'],
                    ],
                    'round_count' => 2,
                ],
            ],
            [
                'name' => 'Drag-and-Drop Garden',
                'category' => GameCategory::MouseControl,
                'game_type' => GameType::DragAndDrop,
                'description' => 'Children place safe garden objects into matching pots.',
                'instructions' => 'Move each flower to its matching pot, or use the choose-and-place buttons.',
                'supported_input_methods' => ['mouse', 'touch', 'keyboard'],
                'configuration' => [
                    'items' => [
                        ['name' => 'Red flower', 'value' => 'red_flower', 'expected' => 'Red pot'],
                        ['name' => 'Yellow flower', 'value' => 'yellow_flower', 'expected' => 'Yellow pot'],
                    ],
                    'round_count' => 2,
                ],
            ],
            [
                'name' => 'Scroll Adventure',
                'category' => GameCategory::MouseControl,
                'game_type' => GameType::ScrollAdventure,
                'description' => 'Children scroll through a short contained journey.',
                'instructions' => 'Scroll inside the adventure box to find each flag.',
                'supported_input_methods' => ['mouse', 'touch', 'keyboard'],
                'configuration' => [
                    'items' => [
                        ['name' => 'First flag', 'value' => 'first_flag'],
                        ['name' => 'Bridge flag', 'value' => 'bridge_flag'],
                        ['name' => 'Finish flag', 'value' => 'finish_flag'],
                    ],
                    'round_count' => 3,
                ],
            ],
            [
                'name' => 'Find the Enter Key',
                'category' => GameCategory::KeyboardDiscovery,
                'game_type' => GameType::KeyboardKeyExplorer,
                'description' => 'Children find the Enter key using physical or on-screen input.',
                'instructions' => 'Find Enter. It helps us start a new line or choose something.',
                'supported_input_methods' => ['keyboard', 'touch', 'mouse'],
                'configuration' => [
                    'keys' => [
                        ['key' => 'Enter', 'name' => 'Enter', 'purpose' => 'Starts a new line or chooses'],
                        ['key' => 'Spacebar', 'name' => 'Spacebar', 'purpose' => 'Makes a space'],
                        ['key' => 'Shift', 'name' => 'Shift', 'purpose' => 'Helps make one capital letter'],
                    ],
                    'round_count' => 3,
                ],
            ],
            [
                'name' => 'Keyboard Key Explorer',
                'category' => GameCategory::KeyboardDiscovery,
                'game_type' => GameType::KeyboardKeyExplorer,
                'description' => 'Children explore letters, numbers and important keys.',
                'instructions' => 'Press or tap the key I ask for.',
                'supported_input_methods' => ['keyboard', 'touch', 'mouse'],
                'configuration' => [
                    'keys' => [
                        ['key' => 'A', 'name' => 'A key', 'purpose' => 'Types A'],
                        ['key' => '5', 'name' => 'Number 5', 'purpose' => 'Types 5'],
                        ['key' => 'ArrowRight', 'name' => 'Right arrow', 'purpose' => 'Moves right'],
                    ],
                    'round_count' => 3,
                ],
            ],
            [
                'name' => 'Falling Letters',
                'category' => GameCategory::KeyboardDiscovery,
                'game_type' => GameType::FallingLetters,
                'description' => 'Children press slowly falling letters with accuracy before speed.',
                'instructions' => 'Press the matching letter before it reaches the garden.',
                'supported_input_methods' => ['keyboard', 'touch'],
                'configuration' => [
                    'keys' => [
                        ['key' => 'A', 'name' => 'A'],
                        ['key' => 'S', 'name' => 'S'],
                        ['key' => 'D', 'name' => 'D'],
                        ['key' => 'F', 'name' => 'F'],
                    ],
                    'round_count' => 4,
                ],
            ],
            [
                'name' => 'Arrow-Key Path',
                'category' => GameCategory::KeyboardDiscovery,
                'game_type' => GameType::ArrowKeyPath,
                'description' => 'Children guide a friendly sprout through a short path.',
                'instructions' => 'Use the arrow keys or buttons to follow the path.',
                'supported_input_methods' => ['keyboard', 'touch'],
                'configuration' => [
                    'path' => [
                        ['key' => 'ArrowRight', 'name' => 'Move right'],
                        ['key' => 'ArrowDown', 'name' => 'Move down'],
                        ['key' => 'ArrowRight', 'name' => 'Move right'],
                    ],
                    'round_count' => 3,
                ],
            ],
        ];
    }

    private function clearExistingGameData(): void
    {
        GameResult::query()->delete();
        DB::table('game_session_rounds')->delete();
        GameSession::query()->delete();
        AssignmentItem::query()->whereNotNull('game_version_id')->update(['game_version_id' => null]);
        DB::table('game_definitions')->update(['current_version_id' => null]);
        DB::table('game_versions')->delete();
        DB::table('game_definitions')->delete();
    }
}
