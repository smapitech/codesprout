# CodeSprout Phase 6: Typing Practice and Accuracy Engine

Phase 6 adds a reusable, server-authoritative typing engine for CodeSprout by ChildsBridge Academy. It extends the existing curriculum, assignment, game, and rewards architecture without rebuilding those modules.

## Architecture

The typing engine is organised around:

- Versioned typing exercises: `typing_exercises` hold the stable identity, while `typing_exercise_versions` preserve immutable published content.
- Difficulty profiles: `typing_difficulty_profiles` store declarative, published configuration for age-appropriate practice levels.
- Session lifecycle: `typing_sessions` track child or preview sessions through in-progress, paused, resumed, completed, submitted, awaiting-review, invalidated, abandoned, and expired states.
- Bounded input capture: `typing_event_batches` and `typing_input_events` save only approved exercise-related input, not general keyboard activity.
- Server metrics: `TypingMetricCalculator` calculates first-attempt accuracy, final-text accuracy, correction/error counts, CPM, and gross WPM.
- Event integration: valid completions dispatch `TypingSessionCompleted`, which is consumed by the Phase 5 reward/progress processor.
- Reporting: `TypingReportService` provides role-scoped child, teacher, parent, and administrator summaries.

## Main Services

- `TypingExercisePublicationService`: creates draft exercises, creates replacement draft versions, validates publication, publishes, and archives.
- `TypingContentValidator`: rejects unsafe HTML, JavaScript, PHP, SQL-like content, hidden control characters, unsupported key names, and impossible prompt collections.
- `TypingSessionService`: starts sessions, creates previews, validates ownership, records event batches idempotently, pauses, resumes, completes, and links assignment responses.
- `TypingMetricCalculator`: provides deterministic server-side metrics.
- `TypingAdaptationService`: returns bounded, explainable recommendations from several validated attempts.
- `TypingAuditService`: records material administrative and session completion actions without storing raw typed content in audit metadata.

## Exercise Types

The initial handler registry supports:

- Key discovery
- Letter practice
- Letter-sequence practice
- Word typing
- Sentence typing
- Capital-letter practice
- Number practice
- Special-key practice
- Punctuation practice
- Copy typing
- Listen-and-type
- Calm timed practice
- Typing assessment

The current implementation uses one key-discovery handler and one reusable text-typing handler for the initial functional set. Future specialised handlers can be registered without changing controllers.

## Metrics

First-attempt accuracy:

```text
correct_first_attempt_inputs / total_first_attempt_inputs * 100
```

Final-text accuracy:

```text
max(0, expected_character_count - edit_distance) / max(1, expected_character_count) * 100
```

Speed:

```text
CPM = eligible_typed_characters / active_minutes
gross WPM = eligible_typed_characters / 5 / active_minutes
```

Speed is stored only when the session has enough evidence. Short sessions are reported as not enough practice data rather than failure.

## Security And Privacy

- Child payloads exclude `expected_text` and normalised answer metadata.
- Input batches are size-limited and sequence-validated.
- Duplicate batch UUIDs are idempotent when the payload matches and rejected when conflicting.
- Completed sessions cannot continue scoring.
- Paste and assistive input are recorded neutrally and can require teacher review.
- Preview sessions never create child rewards or progress.
- Parents cannot start or submit typing sessions.
- Teachers only see learners in their assigned classes.
- Reward, XP, badge, and progress changes come only from validated server-side completion events.

## Assignment, Curriculum And Rewards

Typing sessions may reference:

- `lesson_stage_id` for curriculum context.
- `assignment_allocation_id` and `assignment_attempt_id` for assignments.
- `typing_exercise_version_id` on assignment items for typing activities.

On valid completion, the engine dispatches an idempotent `typing.completed` progress event. The Phase 5 reward system evaluates published reward rules, including seeded typing completion stars, XP, Typing Starter badge, and Accuracy Star badge rules.

## Seed Data

The `TypingSeeder` creates:

- 7 difficulty profiles.
- 18 published typing exercise collections.
- 1 draft typing exercise.
- 1 archived typing exercise.
- New, active, paused, resumed, completed, submitted, awaiting-review/paste, abandoned, expired, teacher preview, and administrator preview session examples.
- Assignment-linked typing practice when a sample assignment is available.

Fictional learner data is used only for development seeding.

## Frontend

Child-facing typing screens use the CodeSprout palette and a focused practice shell:

- Cream background
- White rounded cards
- Large controls
- On-screen keyboard teaching aid
- Progress bar with accessible label
- Scoped input field
- Pause, resume, leave, and finish controls
- Calm feedback messages

The player captures keys only while the typing input has focus and cleans up paste listeners when the component unmounts.

## Current Limitations

- QWERTY is the only fully seeded keyboard layout.
- Rich browser tests for screen readers, mobile native keyboards, and reduced-motion behavior are not present in the current test stack.
- Detailed event retention cleanup is represented in schema via `retained_until`; a queued cleanup command/job is prepared for future hardening.
- Teacher assignment authoring UI for typing-specific assignment options is represented by the backend link field and service path; a richer builder screen can be expanded later.

## Phase 7 Preparation

Phase 7 can reuse:

- `TypingExerciseHandler` for structured coding-symbol entry.
- Versioned exercise/content tables for HTML tag typing.
- Bounded input events and server metrics for safe code-entry evidence.
- The progress-event boundary for coding skill and reward integration.
