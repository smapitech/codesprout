# CodeSprout Phase 2 Curriculum Architecture

CodeSprout by ChildsBridge Academy models the one-year programme as a structured learning journey, not as separate games. Phase 2 owns curriculum authoring, publication, teacher preview, and the child-facing read-only journey.

## Hierarchy

The curriculum hierarchy is:

```text
Curriculum
└── World
    └── Weekly Unit
        └── Lesson
            └── Stage
```

- A `Curriculum` is the complete programme, such as the one-year CodeSprout programme.
- A `World` is a story-based learning environment, normally one month of learning.
- A `Weekly Unit` groups lessons for a specific week.
- A `Lesson` is one teaching session.
- A `Stage` is one short child activity inside a lesson.

## Seeded One-Year Programme

The development seed creates:

- 1 curriculum
- 12 worlds
- 48 weekly units
- 144 lessons
- 576 lesson stages
- 48 skills

The 12 worlds are:

1. Computer Discovery
2. Mouse Adventure
3. Keyboard Island
4. Typing Jungle
5. Capital City
6. Symbol Mountain
7. Logic Land
8. Block Coding Village
9. HTML Builder Bay
10. CSS Colour Kingdom
11. JavaScript Action City
12. Young Creator Studio

The seed blueprint lives in `database/seeders/Data/CodeSproutCurriculumSeedData.php`. The seeder is intentionally data-driven so curriculum authors can extend the programme without placing hundreds of records in one large method.

## Database Relationships

- `curricula` has many `curriculum_worlds`.
- `curriculum_worlds` belongs to `curricula` and has many `curriculum_units`.
- `curriculum_units` belongs to `curriculum_worlds` and has many `lessons`.
- `lessons` belongs to `curriculum_units`, has many `lesson_stages`, and belongs to many `skills`.
- `lesson_stages` belongs to `lessons` and belongs to many `skills`.
- `skills` are reusable learning objectives linked through `lesson_skill` and `stage_skill`.
- `curriculum_world_prerequisites`, `lesson_prerequisites`, and `lesson_stage_prerequisites` store unlock dependencies.

Scoped unique constraints protect slugs, display order, and numbering within each parent. Published records are archived rather than hard-deleted through the service layer.

## Enums And Statuses

Do not scatter raw strings through controllers or views. Use the application enums:

- `App\Enums\ContentStatus`: `draft`, `published`, `archived`
- `App\Enums\DifficultyLevel`: `introductory`, `easy`, `developing`, `independent`, `challenge`
- `App\Enums\StageType`: `introduction`, `demonstration`, `guided_practice`, `game_mission`, `independent_practice`, `review`, `assessment`, `project`
- `App\Enums\InteractionType`: `watch`, `listen`, `find`, `select`, `match`, `drag_drop`, `order_sequence`, `type`, `fill_blank`, `debug`, `build`, `explain`

## Publication Workflow

Publication is handled by `App\Services\Curriculum\CurriculumPublicationService`.

Before publication:

- A curriculum must have a title, slug, description, age range, duration, lessons-per-week value, and at least one world.
- A world must have a name, description, learning outcomes, display order, and at least one unit.
- A unit must have a title, description, display order, and at least one lesson.
- A lesson must have a title, learner objective, estimated duration, and at least one stage.
- A stage must have a title, valid stage type, valid interaction type, child-facing instruction, and estimated duration.

Publishing a curriculum publishes its full world, unit, lesson, and stage tree inside a transaction. Draft and archived content must not be returned to child routes, teacher previews, or public-facing curriculum APIs.

## Reordering And Duplication

Use `CurriculumOrderingService` for world, unit, lesson, and stage moves. It validates that records belong to the intended parent and swaps display orders inside transactions.

Use `CurriculumDuplicationService` for copies. Duplicate records receive independent slugs and IDs so future edits do not mutate the original content.

When adding drag-and-drop controls, keep the existing move-up and move-down route actions as the accessible keyboard alternative.

## Prerequisites

Use `CurriculumPrerequisiteService` to add prerequisite relationships. It rejects circular dependencies so unlock paths cannot become impossible.

Prerequisite rules are separate from child progress. Phase 2 only prepares the curriculum structure; the future progress engine can replace or extend availability decisions.

## Child Journey Availability

`CurriculumAvailabilityService` is the temporary Phase 2 availability boundary.

Current rule:

- The child primary class world is available when assigned.
- Otherwise, the first published world is available.
- Other published worlds are previewable.
- Draft and archived worlds are hidden.

Do not move this logic into templates. Replace this service when the full progress and reward engine is introduced.

## Import And Export

`CurriculumExportService` exports the hierarchy as versioned JSON:

```json
{
    "schema_version": 1,
    "curriculum": {
        "title": "CodeSprout One-Year Programme",
        "worlds": []
    },
    "skills": []
}
```

`CurriculumImportService` validates the complete payload before writing and wraps imports in a database transaction. Use dry-run validation before importing unfamiliar curriculum data. Imports must not contain executable content.

Admin routes:

- Export: `GET /admin/curriculum/{curriculum}/export`
- Import: `POST /admin/curriculum/import`

Internal JSON route:

- Export: `GET /api/curricula/{curriculum}`

## Adding Content

To add a world:

- Add it through admin curriculum management, or extend the structured seed/import JSON.
- Provide name, slug, world number, child-safe description, outcomes, colours, order, and status.
- Publish only after the publication service passes.

To add a lesson:

- Attach it to the correct weekly unit.
- Provide a descriptive title, learner objective, estimated minutes, difficulty, teacher notes where useful, and at least one stage before publication.
- Link relevant skills through `lesson_skill`.

To add a stage:

- Attach it to the correct lesson.
- Use a valid stage type and interaction type.
- Keep `instruction_text` short, concrete, and child-friendly.
- Add optional encouragement text, audio references, star value, configuration, and skill links.

## Role Access

- Administrators manage curriculum content and publication.
- Teachers browse assigned published curriculum in read-only mode.
- Children view only published and available or previewable journey content.
- Parents do not manage curriculum content.

Policies, route middleware, and scoped service checks work together to prevent IDOR access and accidental draft exposure.

## Future Module Connections

Later game, assignment, and progress modules should reference curriculum records through stable foreign keys:

- `curriculum_id`
- `curriculum_world_id`
- `curriculum_unit_id`
- `lesson_id`
- `lesson_stage_id`
- `skill_id`

Do not run learner code directly from curriculum configuration. Stage configuration should remain validated metadata that a safe, purpose-built engine interprets.
