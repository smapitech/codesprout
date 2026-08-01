# CodeSprout Phase 3 Assignment Architecture

CodeSprout by ChildsBridge Academy uses assignments as guided learning missions that sit on top of the Phase 2 curriculum. Phase 3 provides the assignment library, versioning, allocation, child submissions, automatic grading, teacher marking, parent visibility, audit logging, and future reward/event boundaries.

## Scope

Phase 3 includes:

- Reusable assignment records and immutable published versions.
- Question/activity items with child-safe options.
- Links from assignment versions to curriculum levels and skills.
- Class, learner group, and individual child allocation.
- Child mission lists, attempt start, autosave, resume, submission, attachments, and completion screens.
- Teacher assignment builder, publishing, allocation, marking queue, feedback, return-for-retry, and completion actions.
- Parent assignment overview for linked children only.
- Automatic grading for simple interaction families.
- Manual marking for open-ended work.
- Audit records and assignment lifecycle events.

Phase 3 does not include the specialised game engines. Phase 4 should implement computer-parts games, mouse-control games, keyboard-discovery games, shared game sessions, spoken game instructions, and performance recording.

## Database Tables

Core authoring tables:

- `assignments`: stable reusable identity, owner, creator, type, status, current version, and archive timestamp.
- `assignment_versions`: versioned instructions, timing, scoring, feedback settings, publication data, and settings.
- `assignment_items`: questions or activities inside a version.
- `assignment_item_options`: selectable, matching, or ordering options for items. Correctness metadata is never sent to child-facing payloads.
- `assignment_curriculum_links`: links an assignment version to exactly one curriculum level per row.
- `assignment_skill`: links an assignment version to practised skills.

Allocation and submission tables:

- `learner_groups`: teacher-created learner groups within a class.
- `learner_group_members`: children in learner groups.
- `assignment_allocations`: published version allocated to exactly one class, group, or child.
- `assignment_attempts`: child attempts with status, timestamps, scores, late flag, and counters.
- `assignment_responses`: saved child responses with auto/manual scores and teacher comments.
- `submission_attachments`: private learner uploads for allowed activity types.

Assessment tables:

- `assignment_feedback`: teacher feedback visible to child and/or parent.
- `assessment_rubrics`: reusable rubric definitions.
- `assessment_rubric_criteria`: rubric criteria and maximum points.
- `assignment_rubric_scores`: awarded rubric scores for attempts.

## Enums

Phase 3 uses application enums instead of scattered status strings:

- `AssignmentType`: mission, quiz, project, observation, practice.
- `QuestionType`: all 24 supported assignment question/activity types.
- `AllocationStatus`: scheduled, open, closed, cancelled.
- `AttemptStatus`: not_started, in_progress, submitted, awaiting_review, marked, returned, completed.
- `AssignmentFeedbackType`: encouragement, correction, achievement, retry_guidance, general.
- `AssignmentFeedbackMode`: immediate, after_submission, after_due_date, teacher_release.
- `AssignmentScoringMethod`: latest_attempt, highest_attempt, first_attempt, teacher_selected.
- `LateSubmissionPolicy`: block, allow, mark_late.

## Assignment Versioning

`AssignmentVersionService` enforces the safe editing model:

- `assignments` are the long-lived identity.
- `assignment_versions` contain the instructions, items, options, settings, curriculum links, and skills.
- Draft versions can be edited.
- Published versions are not edited in place.
- Saving changes to a published assignment duplicates the published version into a new draft.
- Existing allocations continue pointing at the original published version.
- New allocations can target the latest published version.

This preserves learner work and prevents a teacher edit from changing a mission that children already received.

## Publication Validation

`AssignmentPublicationService` validates draft versions before publication:

- Title is required.
- Child instructions are required.
- At least one item is required.
- Each item must have a valid question type.
- Points must be positive.
- Auto-gradable items must have valid answer metadata.
- Curriculum links must target exactly one curriculum level per row.
- Referenced media must exist.
- Executable media references are rejected.
- Total points must be greater than zero.

Publication recalculates total points, marks the version and assignment as published, updates the current version, and records an audit event.

## Question Handlers

The assignment engine uses `AssignmentQuestionHandlerRegistry` and handlers under `App\Services\Assignments\QuestionHandlers`.

Each handler implements:

- `validateConfiguration()`
- `validateResponse()`
- `transformForLearner()`
- `gradeResponse()`
- `requiresManualReview()`

This keeps question-specific rules out of controllers and avoids one large switch statement.

Implemented handler families:

- `ChoiceQuestionHandler`: multiple choice, image choice, true/false, find/press/select style activities, and simple drag/drop placeholders.
- `MatchingQuestionHandler`: match items and match opening/closing HTML tags.
- `OrderingQuestionHandler`: sequence and code ordering.
- `TypingQuestionHandler`: typing, symbols, HTML tags, fill-in activities, and simple repair prompts.
- `ManualQuestionHandler`: short child response, creative project, and teacher observation.

## Answer Normalisation

Typing and coding comparisons are configurable through item grading configuration:

- Leading/trailing spaces can be trimmed.
- Case sensitivity can be enabled for capital-letter assessment.
- Multiple accepted answers are supported.
- Coding symbols remain significant.

Examples:

- `Ada` and `ada` can be different when case-sensitive grading is enabled.
- `<h1>` is not normalised into `h1`.
- `_`, `-`, `/`, and `\` remain distinct.

## Allocation Rules

`AssignmentAllocationService` enforces allocation safety:

- Only published versions can be allocated.
- Exactly one target is required: class, learner group, or individual child.
- Teachers can allocate only to their assigned classes.
- Teachers can allocate only to groups in their assigned classes.
- Teachers can allocate only to children enrolled in their assigned classes.
- Administrators may manage all assignment records.
- Cancelled allocations cannot be started.
- Closed allocations block attempts unless late submission policy permits future expansion.

Allocation creation, cancellation, reopen, and closure record audit events.

## Child Attempts

`AssignmentAttemptService` owns child attempt lifecycle:

- Starts an attempt when a child opens an available mission.
- Reuses an unfinished attempt for resume support.
- Enforces attempt limits.
- Saves responses during autosave.
- Updates `last_activity_at`.
- Blocks edits to other children’s attempts.
- Blocks edits after submission or completion.
- Submits inside a transaction.
- Returns the same submitted attempt for duplicate submit requests.
- Dispatches lifecycle events for future rewards/progress integration.

Correct-answer metadata is stripped from learner payloads and from saved child response input.

## Teacher Marking

Teachers review attempts inside their teaching scope. Marking supports:

- Automatic score review.
- Manual score entry capped by item/attempt maximums.
- Written feedback.
- Optional audio feedback path.
- Feedback visibility flags for child and parent.
- Return for retry.
- Mark complete.
- Rubric score storage.
- Audit records for score changes.

## Parent Visibility

Parents see assignment attempts for linked children only. Parent-facing summaries include:

- Child name.
- Assignment title.
- Status.
- Due date.
- Submission date.
- Score.
- Teacher feedback when released to parent.
- Retry flag.
- Practised skills.

Parents cannot create assignments, answer questions, submit attempts, or view another child’s records.

## Child-Facing UI

The child mission pages continue the CodeSprout visual direction:

- Soft cream background.
- White rounded cards.
- Leafy teal, warm coral, sunflower yellow, lavender, and sky-blue accents.
- Large touch-friendly controls.
- Friendly mission labels such as “Ready to Play,” “Continue Mission,” and “Great Work!”
- One-question-at-a-time player.
- Progress indicator.
- Hint and repeat-instruction controls.
- Encouraging completion state.

Correctness information is not hidden in forms or JavaScript state.

## Events And Future Rewards Boundary

Phase 3 dispatches:

- `AssignmentStarted`
- `AssignmentSubmitted`
- `AssignmentMarked`
- `AssignmentCompleted`
- `AssignmentReturnedForRetry`

These are intentionally lightweight. Phase 4 or later reward/progress services should subscribe to these events rather than hard-coding star mutations inside controllers.

## Security Notes

- Assignment routes are protected by role middleware and policies.
- Form requests validate authoring, allocation, responses, marking, and attachments.
- Attachments reject executable uploads and use private storage boundaries.
- Teacher-supplied text is rendered through React escaping unless a future rich-text sanitizer is introduced.
- Learner-submitted code is stored as response data only and must never execute in the main application context.
- Audit logs are written for publication, allocation lifecycle, attempt lifecycle, marking, and completion.

## Seed Data

`database/seeders/AssignmentSeeder.php` creates fictional development records covering:

- Multiple-choice assignment.
- Matching assignment.
- Keyboard-key assignment.
- Typing assignment.
- Symbol assignment.
- HTML tag-building assignment.
- Code-ordering assignment.
- Creative project.
- Teacher-observation activity.
- Allocations to class, group, and child targets.
- In-progress, submitted, and marked attempts.
- Teacher feedback.

The full development seed is safe to recreate with:

```bash
php artisan migrate:fresh --seed
```

## Testing

The Phase 3 feature coverage lives in `tests/Feature/Assignments/AssignmentManagementTest.php`. It verifies role access, draft privacy, teacher allocation scope, parent restrictions, child isolation, draft allocation blocking, immutable published versions, child-safe payloads, automatic grading, attempt limits, autosave/resume, duplicate submit idempotency, manual score caps, return-for-retry, parent feedback scoping, report scoping, executable upload rejection, and audit records.
