<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    public function test_migrations_and_seeders_build_the_sample_foundation_from_scratch()
    {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
        ]);

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('permissions', 22);
        $this->assertDatabaseCount('academic_cohorts', 1);
        $this->assertDatabaseCount('classes', 1);
        $this->assertDatabaseCount('class_teacher_assignments', 1);
        $this->assertDatabaseCount('class_enrolments', 2);
        $this->assertDatabaseCount('parent_child_relationships', 2);
        $this->assertDatabaseCount('application_settings', 6);
        $this->assertDatabaseCount('audit_logs', 160);
        $this->assertDatabaseCount('curricula', 1);
        $this->assertDatabaseCount('curriculum_worlds', 12);
        $this->assertDatabaseCount('curriculum_units', 48);
        $this->assertDatabaseCount('lessons', 144);
        $this->assertDatabaseCount('lesson_stages', 576);
        $this->assertDatabaseCount('skills', 48);
        $this->assertDatabaseCount('assignments', 10);
        $this->assertDatabaseCount('assignment_versions', 10);
        $this->assertDatabaseCount('assignment_allocations', 7);
        $this->assertDatabaseCount('assignment_attempts', 3);
        $this->assertDatabaseCount('learner_groups', 1);
        $this->assertDatabaseCount('game_definitions', 11);
        $this->assertDatabaseCount('game_versions', 11);
        $this->assertDatabaseCount('game_sessions', 4);
        $this->assertDatabaseCount('game_results', 2);
        $this->assertDatabaseCount('learner_levels', 7);
        $this->assertDatabaseCount('badge_definitions', 21);
        $this->assertDatabaseCount('reward_rules', 19);
        $this->assertDatabaseCount('progress_events', 10);
        $this->assertDatabaseCount('learner_progress_profiles', 2);
        $this->assertDatabaseCount('reward_ledger_entries', 29);
        $this->assertDatabaseCount('badge_awards', 7);
        $this->assertDatabaseCount('streak_records', 3);
        $this->assertDatabaseCount('curriculum_progress_records', 3);
        $this->assertDatabaseCount('skill_progress_records', 7);
        $this->assertDatabaseCount('celebrations', 32);
        $this->assertDatabaseCount('progress_snapshots', 2);
        $this->assertDatabaseCount('typing_difficulty_profiles', 7);
        $this->assertDatabaseCount('typing_exercises', 20);
        $this->assertDatabaseCount('typing_exercise_versions', 20);
        $this->assertDatabaseCount('typing_content_items', 26);
        $this->assertDatabaseCount('typing_sessions', 10);
        $this->assertDatabaseCount('typing_results', 4);
        $this->assertDatabaseCount('typing_key_statistics', 18);
        $this->assertDatabaseCount('html_tag_policies', 1);
        $this->assertDatabaseCount('html_exercises', 20);
        $this->assertDatabaseCount('html_exercise_versions', 20);
        $this->assertDatabaseCount('html_exercise_requirements', 34);
        $this->assertDatabaseCount('project_templates', 10);
        $this->assertDatabaseCount('project_template_versions', 10);
        $this->assertDatabaseCount('learner_webpage_projects', 4);
        $this->assertDatabaseCount('project_revisions', 5);
        $this->assertDatabaseCount('project_autosaves', 2);
        $this->assertDatabaseCount('html_attempts', 4);
        $this->assertDatabaseCount('html_attempt_responses', 2);
        $this->assertDatabaseCount('html_validation_results', 2);
        $this->assertDatabaseCount('html_requirement_results', 2);
        $this->assertDatabaseCount('project_reviews', 2);
        $this->assertDatabaseCount('project_showcase_entries', 1);

        $this->assertDatabaseHas('users', ['email' => 'admin@childsbridge.test']);
        $this->assertDatabaseHas('users', ['email' => 'teacher@childsbridge.test']);
        $this->assertDatabaseHas('users', ['email' => 'parent@childsbridge.test']);
        $this->assertDatabaseHas('child_profiles', ['learner_id' => 'CB-LEARN-1001']);
        $this->assertDatabaseHas('child_profiles', ['learner_id' => 'CB-LEARN-1002']);
        $this->assertDatabaseHas('roles', ['name' => RoleName::Administrator->value]);
        $this->assertDatabaseHas('permissions', ['name' => 'access admin dashboard']);
        $this->assertDatabaseHas('classes', ['class_code' => 'CB-KEY-01']);
        $this->assertDatabaseHas('academic_cohorts', ['academic_year' => '2026-2027']);
        $this->assertDatabaseHas('curricula', ['slug' => 'codesprout-one-year-programme']);
        $this->assertDatabaseHas('curriculum_worlds', ['slug' => 'keyboard-island']);
        $this->assertDatabaseHas('game_definitions', ['slug' => 'computer-part-explorer']);
        $this->assertDatabaseHas('game_definitions', ['slug' => 'draft-sprout-lab']);
        $this->assertDatabaseHas('learner_levels', ['slug' => 'curious-sprout']);
        $this->assertDatabaseHas('badge_definitions', ['slug' => 'key-explorer']);
        $this->assertDatabaseHas('reward_rules', ['slug' => 'mission-completion-stars']);
        $this->assertDatabaseHas('typing_exercises', ['slug' => 'first-three-letter-words']);
        $this->assertDatabaseHas('typing_difficulty_profiles', ['slug' => 'key-explorer']);
        $this->assertDatabaseHas('html_exercises', ['slug' => 'heading-adventure']);
        $this->assertDatabaseHas('project_templates', ['slug' => 'my-first-webpage']);
        $this->assertDatabaseHas('reward_rules', ['slug' => 'html-completion-stars']);
    }
}
