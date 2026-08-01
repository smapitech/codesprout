<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_type', 50)->index();
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
        });

        Schema::create('assignment_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title')->nullable();
            $table->longText('short_description')->nullable();
            $table->longText('child_instructions')->nullable();
            $table->longText('teacher_instructions')->nullable();
            $table->string('audio_instruction_path')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(10);
            $table->string('difficulty_level', 40)->default('introductory');
            $table->unsignedSmallInteger('total_points')->default(0);
            $table->unsignedTinyInteger('default_attempt_limit')->default(1);
            $table->string('feedback_mode', 40)->default('after_submission');
            $table->string('scoring_method', 40)->default('latest_attempt');
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'version_number']);
            $table->index(['assignment_id', 'status']);
        });

        Schema::table('assignments', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('assignment_versions')->nullOnDelete();
        });

        Schema::create('assignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_version_id')->constrained('assignment_versions')->cascadeOnDelete();
            $table->string('title');
            $table->longText('prompt_text')->nullable();
            $table->string('audio_prompt_path')->nullable();
            $table->string('image_path')->nullable();
            $table->string('question_type', 80)->index();
            $table->string('interaction_type', 40)->index();
            $table->unsignedSmallInteger('points')->default(1);
            $table->boolean('is_required')->default(true);
            $table->longText('hint_text')->nullable();
            $table->string('hint_audio_path')->nullable();
            $table->longText('explanation_text')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->json('configuration')->nullable();
            $table->json('grading_configuration')->nullable();
            $table->timestamps();

            $table->unique(['assignment_version_id', 'display_order']);
        });

        Schema::create('assignment_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_item_id')->constrained('assignment_items')->cascadeOnDelete();
            $table->string('option_text')->nullable();
            $table->string('image_path')->nullable();
            $table->string('option_value')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->string('matching_key')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['assignment_item_id', 'display_order']);
        });

        Schema::create('assignment_curriculum_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_version_id')->constrained('assignment_versions')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('curriculum_world_id')->nullable()->constrained('curriculum_worlds')->cascadeOnDelete();
            $table->foreignId('curriculum_unit_id')->nullable()->constrained('curriculum_units')->cascadeOnDelete();
            $table->foreignId('curriculum_lesson_id')->nullable()->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained('lesson_stages')->cascadeOnDelete();
            $table->timestamps();

            $table->index('assignment_version_id');
            $table->index(['curriculum_id', 'curriculum_world_id', 'curriculum_unit_id', 'curriculum_lesson_id', 'lesson_stage_id'], 'assignment_curriculum_target_index');
        });

        Schema::create('assignment_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_version_id')->constrained('assignment_versions')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->unsignedTinyInteger('emphasis_level')->default(1);
            $table->timestamps();

            $table->unique(['assignment_version_id', 'skill_id']);
            $table->index(['skill_id', 'assignment_version_id']);
        });

        Schema::create('learner_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'name']);
        });

        Schema::create('learner_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_group_id')->constrained('learner_groups')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['learner_group_id', 'child_id']);
            $table->index(['child_id', 'learner_group_id']);
        });

        Schema::create('assignment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_version_id')->constrained('assignment_versions')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('learner_groups')->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('available_from')->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('closes_at')->nullable()->index();
            $table->unsignedTinyInteger('attempt_limit')->nullable();
            $table->string('scoring_method', 40)->nullable();
            $table->boolean('show_score_to_child')->default(true);
            $table->boolean('show_correct_answers')->default(false);
            $table->boolean('allow_late_submission')->default(false);
            $table->string('late_submission_policy', 40)->default('block');
            $table->string('status', 30)->default('scheduled')->index();
            $table->timestamps();

            $table->index(['class_id', 'status']);
            $table->index(['group_id', 'status']);
            $table->index(['child_id', 'status']);
            $table->index(['assignment_version_id', 'status']);
        });

        Schema::create('assignment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_allocation_id')->constrained('assignment_allocations')->cascadeOnDelete();
            $table->foreignId('assignment_version_id')->constrained('assignment_versions')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 30)->default('not_started')->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->decimal('auto_score', 8, 2)->default(0);
            $table->decimal('manual_score', 8, 2)->default(0);
            $table->decimal('final_score', 8, 2)->default(0);
            $table->decimal('maximum_score', 8, 2)->default(0);
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->unsignedSmallInteger('hints_used')->default(0);
            $table->boolean('is_late')->default(false);
            $table->timestamps();

            $table->unique(['assignment_allocation_id', 'child_id', 'attempt_number'], 'assignment_attempt_unique');
            $table->index(['child_id', 'status']);
            $table->index(['assignment_allocation_id', 'status']);
        });

        Schema::create('assignment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_attempt_id')->constrained('assignment_attempts')->cascadeOnDelete();
            $table->foreignId('assignment_item_id')->constrained('assignment_items')->cascadeOnDelete();
            $table->json('response_data')->nullable();
            $table->longText('text_response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('auto_score', 8, 2)->default(0);
            $table->decimal('manual_score', 8, 2)->default(0);
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable()->index();
            $table->longText('teacher_comment')->nullable();
            $table->timestamps();

            $table->unique(['assignment_attempt_id', 'assignment_item_id']);
        });

        Schema::create('assignment_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_attempt_id')->constrained('assignment_attempts')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('feedback_text');
            $table->string('audio_feedback_path')->nullable();
            $table->string('feedback_type', 40)->default('general');
            $table->boolean('returned_for_retry')->default(false);
            $table->boolean('visible_to_child')->default(true);
            $table->boolean('visible_to_parent')->default(true);
            $table->timestamps();

            $table->index(['assignment_attempt_id', 'feedback_type']);
        });

        Schema::create('assessment_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assessment_rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_rubric_id')->constrained('assessment_rubrics')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('maximum_points')->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['assessment_rubric_id', 'display_order']);
        });

        Schema::create('assignment_rubric_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_attempt_id')->constrained('assignment_attempts')->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained('assessment_rubric_criteria')->cascadeOnDelete();
            $table->decimal('awarded_points', 8, 2)->default(0);
            $table->text('teacher_comment')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assignment_attempt_id', 'rubric_criterion_id']);
        });

        Schema::create('submission_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_attempt_id')->constrained('assignment_attempts')->cascadeOnDelete();
            $table->foreignId('assignment_item_id')->nullable()->constrained('assignment_items')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(['assignment_attempt_id', 'assignment_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_attachments');
        Schema::dropIfExists('assignment_rubric_scores');
        Schema::dropIfExists('assessment_rubric_criteria');
        Schema::dropIfExists('assessment_rubrics');
        Schema::dropIfExists('assignment_feedback');
        Schema::dropIfExists('assignment_responses');
        Schema::dropIfExists('assignment_attempts');
        Schema::dropIfExists('assignment_allocations');
        Schema::dropIfExists('learner_group_members');
        Schema::dropIfExists('learner_groups');
        Schema::dropIfExists('assignment_skill');
        Schema::dropIfExists('assignment_curriculum_links');
        Schema::dropIfExists('assignment_item_options');
        Schema::dropIfExists('assignment_items');
        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('assignment_versions');
        Schema::dropIfExists('assignments');
    }
};
