# CodeSprout Phase 7: Early HTML Coding and Webpage Builder Engine

Phase 7 adds a safe, versioned HTML learning layer on top of the existing curriculum, assignment, typing, rewards and progress foundations.

## Architecture

- HTML exercises are authored as drafts and published as immutable `html_exercise_versions`.
- Starter projects are authored through immutable `project_template_versions`.
- Learner work is stored in `learner_webpage_projects` with append-style `project_revisions` and bounded `project_autosaves`.
- Authoritative validation is handled server-side by `App\Services\Html\HtmlSanitizer` and `App\Services\Html\HtmlValidationService`.
- Child-friendly guidance is deterministic and rule-based through `HtmlGuidanceService`.
- Completion and project approval dispatch `HtmlExerciseCompleted` and `WebpageProjectCompleted` events for the Phase 5 progress engine.
- Phase 6 typing evidence is reused for coding-symbol readiness summaries through `HtmlReportService::readiness()`.

## Safety Model

- Child HTML is never rendered directly into the application DOM.
- Server sanitisation uses PHP DOM parsing plus explicit tag, attribute and protocol allowlists.
- React previews use sandboxed iframes with restrictive CSP in `SafePreview`.
- Unsupported tags, event handlers, unsafe URLs, iframes, forms and embeds are removed or rejected before preview.
- Parents only see approved, teacher-released showcase revisions.
- Teacher-only notes are excluded from parent summaries.

## Seeded Content

- 20 HTML learning exercises from Coding Symbol Explorer through Starter Portfolio.
- 10 starter webpage templates, including My First Webpage, A Friendly Robot and Computer Care Guide.
- Fictional learner attempts and projects covering valid completion, unsafe invalidation, previews, awaiting review, requested changes and approved showcase.
- HTML reward rules for valid exercise completion and teacher-approved webpage completion.

## Phase 8 Extension Points

- `html_tag_policies.allowed_attributes` can be extended with approved style tokens later.
- `ProjectTemplateVersion.project_configuration` already carries project mode and preview options.
- `HtmlValidationService` requirement evaluation can accept future CSS-safe checks without changing learner project history.
- `VisualBlockBuilder` generates deterministic HTML blocks and can later map to approved visual style blocks.

## Integration Amendment

The role dashboards now surface the Phase 7 engine instead of leaving it as a disconnected module:

- Administrators can create role accounts and manage class, teacher, learner and parent connections from School Management.
- Teachers receive class-scoped assignment and HTML project review queues.
- Parents receive linked-child progress, released assignment results, released feedback and approved project status only.
- Child dashboard missions are resolved from authenticated assignments, published HTML/typing activities and active saved projects; fictional mission progress is not used.
- Live child previews are sent to rate-limited, owner-scoped endpoints and are sanitised against the exact published tag policy before being rendered in a scriptless iframe.
- Arbitrary remote image requests are rejected; only approved local CodeSprout assets may be used in image sources.
- Phase 7 routes and navigation are controlled by `codesprout.features.html_learning_engine`, with extension flags for the editor, visual builder, projects, reviews, parent preview, showcase and adaptation.
