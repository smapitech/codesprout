# ChildsBridge CodeSprout

CodeSprout is a production-oriented Laravel 12 and React/Inertia foundation for a one-year, game-based computer readiness and early coding programme for children aged 6 to 7.

## Dashboard Login

Use the adult login page for administrator, teacher and parent dashboards. Use the learner login page for child dashboards.

- Adult login: `http://localhost/login`
- Learner login: `http://localhost/child-login`
- Administrator: `admin@childsbridge.test` / `Password123!`
- Teacher: `teacher@childsbridge.test` / `Password123!`
- Parent: `parent@childsbridge.test` / `Password123!`
- Child learner ID: `CB-LEARN-1001` / PIN `1234`
- Child learner ID: `CB-LEARN-1002` / PIN `2468`

## What is in Phase 1

- Laravel web application with Vite, Inertia React and TypeScript
- Role-aware access for administrators, teachers, parents and children
- Spatie-based roles and permissions
- Secure child PIN login with hashing and rate limiting
- Parent-child and teacher-class relationship foundations
- Placeholder dashboards for all four roles
- Child-safe design tokens and a responsive learning-first layout
- Seeded development data and automated tests

## What is in Phase 2

- Curriculum, world, unit, lesson and stage foundation
- Publication validation, draft/published/archived status handling and safe read-only child browsing
- Admin curriculum builder, preview and nested move/duplicate route foundations
- Teacher curriculum browsing and preview access to published content only
- Child journey view that hides draft and archived curriculum content
- Curriculum import/export services backed by structured JSON
- Curriculum ordering, duplication and prerequisite validation services
- Additional tests for curriculum access, publication rules, import rollback and ordering

Full Phase 2 architecture notes are documented in `docs/curriculum-phase-2.md`.

## What is in Phase 3

- Assignment library, versioning and immutable published versions
- Assignment builder foundations for administrators and teachers
- Question-handler architecture for automatic and manual grading
- Class, learner-group and individual-child allocation
- Child “My Missions” experience with start, autosave, resume and submit flows
- Teacher marking queue, feedback, return-for-retry and completion flows
- Parent assignment visibility for linked children only
- Assignment reports, audit records and lifecycle events for future rewards/progress work

Full Phase 3 architecture notes are documented in `docs/assignment-phase-3.md`.

## What is in Phase 4

- Approved game definitions and immutable game versions
- Handler-based game engine for computer, mouse and keyboard activities
- Game sessions with start, pause, resume, completion and performance recording
- Child-safe game payloads that hide expected answers
- Assignment-game integration through assignment items
- Curriculum launch context through lesson stages
- Teacher game library, previews and result reports
- Parent released-result summaries for linked children
- Game lifecycle events for future rewards and progress work

Full Phase 4 architecture notes are documented in `docs/game-engine-phase-4.md`.

## Connected Administration and Phase 7

- Professional School Management workspace for creating administrator, teacher, parent and child accounts
- Class creation, teacher assignment, child enrolment and parent-child linking with audit records
- Role-scoped teacher and parent dashboards backed by assignments, progress, released feedback and HTML project data
- Dynamic child dashboard missions from authenticated allocations, published exercises and active projects
- Phase 7 HTML exercise and project versioning, server-side sanitisation, structural validation and teacher review
- Debounced server-sanitised live preview rendered in a scriptless sandbox with restrictive CSP
- Configurable Phase 7 feature flags that hide disabled routes and navigation without deleting learner work
- Private, teacher-approved project showcase summaries for linked parents

Full Phase 7 architecture notes are documented in `docs/html-engine-phase-7.md`.

## Curriculum Hierarchy

CodeSprout uses this curriculum hierarchy:

- Curriculum
- World
- Weekly Unit
- Lesson
- Stage

The seeded one-year programme currently contains:

- 1 curriculum
- 12 worlds
- 48 weekly units
- 144 lessons
- 576 stages
- 48 skills

## Architecture Notes

- `users` is the core identity table
- `user_profiles`, `teacher_profiles` and `child_profiles` carry role-specific profile data
- `parent_child_relationships`, `class_teacher_assignments` and `class_enrolments` model school relationships
- `academic_cohorts`, `classes`, `application_settings` and `audit_logs` support the school and platform foundation
- `curricula`, `curriculum_worlds`, `curriculum_units`, `lessons`, `lesson_stages` and `skills` model the learning hierarchy
- `curriculum_world_prerequisites`, `lesson_prerequisites` and `lesson_stage_prerequisites` model unlock order and prerequisite chains
- `assignments`, `assignment_versions`, `assignment_items`, `assignment_allocations`, `assignment_attempts`, `assignment_responses` and related tables model the assignment lifecycle
- `game_definitions`, `game_versions`, `game_sessions`, `game_session_rounds` and `game_results` model safe gameplay and performance recording
- Routes are split by role area in `routes/admin.php`, `routes/teacher.php`, `routes/parent.php` and `routes/child.php`
- The internal curriculum export endpoint lives at `GET /api/curricula/{curriculum}`
- Child login uses hashed PINs and throttling; there is no public registration flow

## Content Statuses

Curriculum content uses three statuses:

- `draft`
- `published`
- `archived`

Publication is validated by service before content becomes visible to teachers or children.

## Curriculum Workflow

- The admin curriculum root lives in `routes/admin.php`
- The curriculum builder shows the full hierarchy and validation state
- Teachers can browse published curriculum in read-only mode
- Children only receive published content in their journey
- Draft or archived worlds are hidden from child routes and child page source

## Import and Export

- Export JSON for a curriculum with `GET /api/curricula/{curriculum}`
- Import JSON through the admin import endpoint at `POST /admin/curriculum/import`
- The JSON payload uses `schema_version`, `curriculum`, `worlds` and `skills`
- The same services are used by the import/export surfaces and the phase 2 seeders

## Curriculum JSON Shape

- `schema_version`
- `curriculum`
    - root metadata
    - `worlds`
        - `units`
            - `lessons`
                - `stages`
- `skills`

The import service validates the full structure before inserting records and wraps writes in a transaction.

## Seeder Structure

- `database/seeders/Data/CodeSproutCurriculumSeedData.php` holds the structured seed blueprint
- `database/seeders/CurriculumSeeder.php` imports the blueprint
- `database/seeders/DatabaseSeeder.php` wires the curriculum seeder into the full development seed
- Seed counts are intentionally stable so tests can confirm the full hierarchy from a clean database

## Development Credentials

These credentials are for local development only and must not be used in production.

- Administrator: `admin@childsbridge.test` / `Password123!`
- Teacher: `teacher@childsbridge.test` / `Password123!`
- Parent: `parent@childsbridge.test` / `Password123!`
- Child learner ID: `CB-LEARN-1001` / PIN `1234`
- Child learner ID: `CB-LEARN-1002` / PIN `2468`

## Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
```

## Run

```bash
php artisan serve
```

## Test

```bash
php artisan test
```

## Notes

- Production should use MySQL via `.env`
- The child dashboard is intentionally playful and touch-friendly
- Public leaderboards are intentionally disabled
- Specialised game engines and detailed progress analytics are intentionally deferred

## Adding Curriculum Content

- Add a new world by extending the structured seed data or importing a validated JSON payload
- Add a lesson by attaching it to the correct weekly unit and giving it at least one stage before publication
- Add a stage by defining a valid stage type, interaction type, instruction text and estimated duration
- The publication service should be the final gate before teachers or children see new content
