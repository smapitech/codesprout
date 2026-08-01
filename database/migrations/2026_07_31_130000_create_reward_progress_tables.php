<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_levels', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('level_number');
            $table->unsignedInteger('xp_threshold');
            $table->longText('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['level_number', 'version']);
            $table->unique(['xp_threshold', 'version']);
        });

        Schema::create('badge_definitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_description');
            $table->longText('long_description')->nullable();
            $table->string('badge_category', 80)->index();
            $table->string('badge_image_path')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('qualification_type', 80)->default('event');
            $table->json('qualification_configuration')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('emphasis', 40)->default('standard');
            $table->boolean('repeatable')->default(false);
            $table->unsignedInteger('maximum_awards')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reward_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug');
            $table->string('event_type', 120)->index();
            $table->string('source_type', 120)->nullable()->index();
            $table->json('eligibility_conditions')->nullable();
            $table->string('reward_type', 60)->index();
            $table->unsignedInteger('reward_amount')->default(0);
            $table->foreignId('badge_definition_id')->nullable()->constrained('badge_definitions')->nullOnDelete();
            $table->string('repeat_policy', 60)->default('once_per_source');
            $table->unsignedInteger('maximum_awards')->nullable();
            $table->unsignedInteger('daily_cap')->nullable();
            $table->timestamp('effective_from')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('priority')->default(100);
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['slug', 'version']);
            $table->index(['event_type', 'source_type', 'status']);
        });

        Schema::create('progress_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('event_type', 120)->index();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_type', 120)->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->nullOnDelete();
            $table->foreignId('curriculum_world_id')->nullable()->constrained('curriculum_worlds')->nullOnDelete();
            $table->foreignId('curriculum_unit_id')->nullable()->constrained('curriculum_units')->nullOnDelete();
            $table->foreignId('curriculum_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained('lesson_stages')->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->json('performance_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('status', 40)->default('pending')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->longText('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'event_type', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('learner_progress_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_level_id')->nullable()->constrained('learner_levels')->nullOnDelete();
            $table->unsignedInteger('total_stars')->default(0);
            $table->unsignedInteger('total_experience')->default(0);
            $table->unsignedInteger('completed_missions')->default(0);
            $table->unsignedInteger('completed_lessons')->default(0);
            $table->unsignedInteger('completed_units')->default(0);
            $table->unsignedInteger('completed_worlds')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_learning_date')->nullable()->index();
            $table->timestamp('progress_calculated_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('reward_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->string('reward_type', 60)->index();
            $table->integer('amount')->default(0);
            $table->foreignId('badge_definition_id')->nullable()->constrained('badge_definitions')->nullOnDelete();
            $table->foreignId('reward_rule_id')->nullable()->constrained('reward_rules')->nullOnDelete();
            $table->string('source_type', 120)->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->foreignId('progress_event_id')->nullable()->constrained('progress_events')->nullOnDelete();
            $table->string('reason');
            $table->string('status', 40)->default('awarded')->index();
            $table->timestamp('awarded_at')->index();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_entry_id')->nullable()->constrained('reward_ledger_entries')->nullOnDelete();
            $table->longText('adjustment_reason')->nullable();
            $table->string('idempotency_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'reward_type', 'awarded_at']);
            $table->index(['source_type', 'source_id', 'reward_type']);
        });

        Schema::create('badge_awards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('badge_definition_id')->constrained('badge_definitions')->restrictOnDelete();
            $table->json('badge_snapshot')->nullable();
            $table->string('source_type', 120)->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->foreignId('progress_event_id')->nullable()->constrained('progress_events')->nullOnDelete();
            $table->timestamp('awarded_at')->index();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('earned')->index();
            $table->timestamp('displayed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['child_id', 'badge_definition_id', 'status']);
        });

        Schema::create('streak_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->date('learning_date')->index();
            $table->unsignedInteger('qualifying_activity_count')->default(1);
            $table->timestamp('first_qualifying_activity_at')->nullable();
            $table->timestamp('last_qualifying_activity_at')->nullable();
            $table->string('timezone', 80)->default('UTC');
            $table->string('status', 40)->default('qualifying')->index();
            $table->timestamps();

            $table->unique(['child_id', 'learning_date', 'timezone']);
        });

        Schema::create('curriculum_progress_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->nullOnDelete();
            $table->foreignId('curriculum_world_id')->nullable()->constrained('curriculum_worlds')->nullOnDelete();
            $table->foreignId('curriculum_unit_id')->nullable()->constrained('curriculum_units')->nullOnDelete();
            $table->foreignId('curriculum_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained('lesson_stages')->nullOnDelete();
            $table->string('status', 40)->default('in_progress')->index();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->unsignedInteger('completed_required_items')->default(0);
            $table->unsignedInteger('total_required_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['child_id', 'curriculum_id', 'curriculum_world_id', 'curriculum_unit_id', 'curriculum_lesson_id', 'lesson_stage_id'], 'curriculum_progress_unique');
            $table->index(['child_id', 'status']);
        });

        Schema::create('skill_progress_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->string('skill_slug')->index();
            $table->string('curriculum_context')->nullable()->index();
            $table->unsignedTinyInteger('current_mastery')->default(0);
            $table->unsignedTinyInteger('highest_mastery')->default(0);
            $table->string('mastery_label', 40)->default('getting_started');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('completed_activities_count')->default(0);
            $table->unsignedInteger('evidence_count')->default(0);
            $table->timestamp('last_evidence_at')->nullable()->index();
            $table->timestamp('calculated_at')->nullable();
            $table->json('evidence_summary')->nullable();
            $table->timestamps();

            $table->unique(['child_id', 'skill_slug', 'curriculum_context'], 'skill_progress_unique');
            $table->index(['child_id', 'mastery_label']);
        });

        Schema::create('celebrations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->string('celebration_type', 80)->index();
            $table->string('heading');
            $table->longText('message');
            $table->json('reward_summary')->nullable();
            $table->foreignId('badge_award_id')->nullable()->constrained('badge_awards')->nullOnDelete();
            $table->foreignId('progress_event_id')->nullable()->constrained('progress_events')->nullOnDelete();
            $table->timestamp('display_after')->nullable();
            $table->timestamp('acknowledged_at')->nullable()->index();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['child_id', 'acknowledged_at']);
        });

        Schema::create('progress_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->date('snapshot_date')->index();
            $table->unsignedInteger('stars')->default(0);
            $table->unsignedInteger('experience')->default(0);
            $table->string('level')->nullable();
            $table->unsignedInteger('streak')->default(0);
            $table->unsignedTinyInteger('curriculum_completion')->default(0);
            $table->json('skill_summary')->nullable();
            $table->json('completed_worlds')->nullable();
            $table->json('badges_earned')->nullable();
            $table->timestamp('generated_at')->index();
            $table->timestamps();

            $table->unique(['child_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_snapshots');
        Schema::dropIfExists('celebrations');
        Schema::dropIfExists('skill_progress_records');
        Schema::dropIfExists('curriculum_progress_records');
        Schema::dropIfExists('streak_records');
        Schema::dropIfExists('badge_awards');
        Schema::dropIfExists('reward_ledger_entries');
        Schema::dropIfExists('learner_progress_profiles');
        Schema::dropIfExists('progress_events');
        Schema::dropIfExists('reward_rules');
        Schema::dropIfExists('badge_definitions');
        Schema::dropIfExists('learner_levels');
    }
};
