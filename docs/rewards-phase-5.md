# CodeSprout Phase 5 Rewards and Learner Progress

Phase 5 adds an event-driven rewards, achievements and learner-progress engine for ChildsBridge CodeSprout.

## Architecture

Progress is driven by validated server-side events, not by child-facing requests or JavaScript totals.

Primary services:

- `ProgressEventProcessor` receives validated learning events and processes them idempotently.
- `RewardRulePublicationService` validates declarative reward rules and versions published-rule edits.
- `RewardAwardService` writes append-focused ledger entries and updates cached learner projections.
- `ExperienceLevelService` derives the learner level from cumulative XP.
- `StreakService` records one qualifying learning day per child/timezone/date.
- `ProgressReportService` prepares scoped child, parent, teacher and administrator summaries.
- `ProgressRecalculationService` rebuilds cached totals from authoritative ledger records.

Existing Phase 3 and Phase 4 events are consumed through listeners:

- `AssignmentCompleted`
- `GameSessionCompleted`

## Data Model

Authoritative and configuration tables:

- `reward_rules`
- `reward_ledger_entries`
- `badge_definitions`
- `badge_awards`
- `learner_levels`
- `progress_events`

Cached projections and reporting tables:

- `learner_progress_profiles`
- `curriculum_progress_records`
- `skill_progress_records`
- `streak_records`
- `celebrations`
- `progress_snapshots`

The reward ledger is append-focused. Corrections should be represented by authorised adjustments or reversals rather than destructive edits.

## Idempotency

Every progress event has an `idempotency_key`. Replaying the same event will not duplicate:

- Stars
- XP
- Badges
- Level celebrations
- Streak days
- Curriculum progress records
- Skill evidence

Ledger entries, badge awards and celebrations also carry unique idempotency keys.

## Reward Rules

Reward rules are declarative and published through `RewardRulePublicationService`.

Supported safe condition fields:

- `minimum_accuracy`
- `maximum_hints`
- `source_slug`
- `skill_slug`
- `first_completion`
- `streak_days`
- `teacher_recognition_slug`
- `requires_completion_status`

Unsafe content is rejected, including script tags, JavaScript URLs, eval-like expressions and raw SQL-like conditions.

Published reward rules are not edited in place. Updating a published rule creates a draft replacement version.

## Seed Data

`RewardSeeder` creates fictional development data:

- 7 level thresholds from `Curious Sprout` to `CodeSprout Champion`
- 16 badge definitions
- 10 published reward rules
- Processed assignment and game completion events
- Learner progress profiles
- Badge awards
- Streak examples
- Progress snapshots

Amara and Noah remain fictional development seed learners only.

## Role Views

Administrator:

- `/admin/rewards`
- Shows reward rules, badge definitions, level thresholds and aggregate reward/progress counts.

Teacher:

- `/teacher/progress`
- Shows progress only for learners in assigned classes.

Parent:

- `/parent/progress`
- Shows encouraging progress summaries only for linked children.

Child:

- `/child/rewards`
- Shows stars, XP, level, streaks, badge collection, skill progress and pending celebrations.

## Phase Boundary

This phase does not build:

- The full typing-course engine
- HTML or JavaScript coding editors
- Reward shops
- Purchasable rewards
- Public leaderboards
- AI learner diagnosis

Phase 6 can submit typing-practice completion and performance events through `ProgressEventProcessor`.
