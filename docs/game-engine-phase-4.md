# CodeSprout Phase 4 Game Engine Architecture

CodeSprout by ChildsBridge Academy uses the Phase 4 game engine for safe computer-discovery, mouse-control and keyboard-discovery activities. The engine is designed around declarative configuration, server-side validation and child-safe session payloads.

## Scope

Phase 4 includes:

- Approved game definitions and immutable game versions.
- Handler-based support for computer-part, mouse-control and keyboard-discovery games.
- Game sessions with pause, resume, abandon and complete states.
- Server-calculated results and performance summaries.
- Spoken-instruction controls as browser progressive enhancement.
- Assignment-game integration through `assignment_items.game_version_id`.
- Curriculum integration through optional `lesson_stage_id` session context.
- Teacher library, preview and results views.
- Parent released-result summaries.
- Domain events for future rewards and progress modules.

Phase 4 does not include the rewards, badges, streaks or full learner-progress analytics engine.

## Core Tables

- `game_definitions`: stable game identity, category, type, status, visibility, creator and current version.
- `game_versions`: immutable published configuration, instruction content, difficulty configuration and supported input methods.
- `game_sessions`: child session state, difficulty, assignment/curriculum context, current round and resumable progress.
- `game_session_rounds`: one record per meaningful generated round, storing expected data server-side and child response data after validation.
- `game_results`: calculated performance summary, score, completion status and parent release flag.
- `assignment_items.game_version_id`: optional link from an assignment item to a published game version.

The engine stores meaningful rounds and results only. It does not store continuous pointer trails, unrelated keystrokes, browser fingerprinting or sensitive device data.

## Enums

- `GameCategory`: Computer Discovery, Mouse Control, Keyboard Discovery.
- `GameType`: the initial supported game types.
- `GameDifficulty`: Extra Slow, Slow, Normal.
- `GameSessionStatus`: ready, in_progress, paused, completed, abandoned, expired, cancelled.
- `GameCompletionStatus`: completed, partial, needs_practice.

## Handler Contract

Handlers implement `App\Services\Games\Contracts\GameHandler`:

- `type()`
- `validateConfiguration()`
- `generateRounds()`
- `sessionPayload()`
- `validateAction()`
- `calculatePerformance()`
- `isComplete()`
- `feedbackFor()`
- `supportedInputMethods()`

Handlers are registered in `GameRegistry`. Adding a new game should require a new handler, configuration schema, child component treatment, tests and registry registration.

## Implemented Game Types

- Computer Part Explorer: identify named computer parts.
- Computer Part Matching: match parts to their purpose.
- Click the Target: practise single click or tap accuracy.
- Double-Click Practice: forgiving double-click interval checks.
- Drag-and-Drop Garden: matching game with keyboard/tap alternative support in the shell.
- Scroll Adventure: contained scroll-practice journey.
- Find the Enter Key: key discovery using physical or on-screen controls.
- Keyboard Key Explorer: letters, numbers and important keys.
- Falling Letters: slow, accuracy-first key matching.
- Arrow-Key Path: simple directional path following.

## Difficulty

Difficulty is recorded on every session. Configuration supports Extra Slow, Slow and Normal values for:

- Round count.
- Target duration.
- Movement speed.
- Hint delay.
- Double-click tolerance.
- Falling-letter speed.

Difficulty labels avoid shame-based language and do not change the educational objective.

## Session Lifecycle

`GameSessionService` owns lifecycle behaviour:

- Start validates a published game version and authorised context.
- Sessions store generated rounds for resume and validation.
- Actions validate through the correct handler.
- Pause/resume update state and dispatch events.
- Completion is transactional and idempotent.
- Completed sessions cannot continue scoring.
- Child access is scoped to the owning child.

Child payloads exclude future-round answers and expected values.

## Assignment Integration

Assignments link to games through `assignment_items.game_version_id`.

When a session starts from an assignment:

- The allocation must be accessible to the child.
- The assignment attempt must belong to the child.
- The attempt must match the allocation.
- The assignment item must belong to the attempt version.
- The assignment item’s linked game version must match the requested game.

On completion, the game result updates the assignment response with validated score information. Client-submitted fake totals are ignored.

## Curriculum Integration

`game_sessions.lesson_stage_id` supports launch context from the Phase 2 learning journey. Availability should remain controlled by curriculum publication, child enrolment and future progress services.

## Spoken Instructions

The child player provides written instructions and a user-triggered Repeat button. Browser speech synthesis is used only as progressive enhancement:

- No speech starts before child action.
- The page works without speech support.
- No sensitive text is sent to external services.
- Children can ignore or repeat written instructions.

## Security

- Game routes are protected by existing role middleware.
- Form requests validate admin game definitions, versions, starts and actions.
- Per-handler validators reject unsupported or executable configuration.
- The server validates actions and calculates scores.
- Child payloads remove correct-answer and expected-value data.
- Assignment-linked sessions verify allocation, attempt, item and game-version relationships.
- Completion uses transactions and idempotency.
- Rate limits protect action, pause, resume and completion endpoints.
- Audit records are created for publication, archival, version creation, session start and completion.

## Reports

`GameReportService` provides:

- Sessions started.
- Sessions completed.
- Sessions abandoned.
- Completion rate.
- Average accuracy.
- Average completion time.
- Hints used.
- Teacher-scoped result rows.
- Parent released result rows for linked children only.

## Seed Data

`GameSeeder` creates fictional development data for:

1. Computer Part Explorer
2. Computer Part Matching
3. Click the Target
4. Double-Click Practice
5. Drag-and-Drop Garden
6. Scroll Adventure
7. Find the Enter Key
8. Keyboard Key Explorer
9. Falling Letters
10. Arrow-Key Path

It also creates:

- One draft game.
- One archived game.
- Multiple difficulty configurations.
- An assignment-linked game.
- Curriculum-linked sessions.
- In-progress, paused and completed sessions.
- Released sample parent result.
- Game audit records.

## Testing

`tests/Feature/Games/GameEngineTest.php` verifies role access, draft/archive isolation, immutable game versions, executable configuration rejection, session lifecycle, child isolation, idempotent completion, handler grading, keyboard normalisation, assignment-game context safety, report scoping and audit records.
