<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('html_tag_policies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->json('allowed_tags');
            $table->json('allowed_attributes');
            $table->json('allowed_protocols');
            $table->json('resource_limits');
            $table->string('status')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('checksum', 64)->index();
            $table->timestamps();
        });

        Schema::create('html_exercises', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('exercise_type')->index();
            $table->text('description')->nullable();
            $table->text('child_instructions');
            $table->text('teacher_instructions')->nullable();
            $table->string('status')->index();
            $table->foreignId('current_version_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('html_exercise_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('html_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('exercise_type')->index();
            $table->json('content_configuration');
            $table->foreignId('html_tag_policy_id')->constrained()->restrictOnDelete();
            $table->json('completion_configuration');
            $table->json('assistance_configuration')->nullable();
            $table->json('preview_configuration')->nullable();
            $table->json('assessment_configuration')->nullable();
            $table->json('accessibility_configuration')->nullable();
            $table->string('status')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->string('content_checksum', 64)->index();
            $table->timestamps();

            $table->unique(['html_exercise_id', 'version_number']);
        });

        Schema::table('html_exercises', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('html_exercise_versions')->nullOnDelete();
        });

        Schema::create('html_exercise_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('html_exercise_version_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_type')->index();
            $table->string('tag_name')->nullable()->index();
            $table->string('attribute_name')->nullable();
            $table->text('expected_value')->nullable();
            $table->foreignId('parent_requirement_id')->nullable()->constrained('html_exercise_requirements')->nullOnDelete();
            $table->unsignedInteger('minimum_count')->nullable();
            $table->unsignedInteger('maximum_count')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('required')->default(true);
            $table->decimal('scoring_weight', 6, 2)->default(1);
            $table->json('safe_configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('project_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->foreignId('current_version_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('starter_source');
            $table->longText('sanitised_starter_source');
            $table->foreignId('html_tag_policy_id')->constrained()->restrictOnDelete();
            $table->json('project_configuration');
            $table->json('checklist_configuration');
            $table->json('validation_configuration');
            $table->json('preview_configuration')->nullable();
            $table->json('rubric_configuration')->nullable();
            $table->string('status')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->string('content_checksum', 64)->index();
            $table->timestamps();

            $table->unique(['project_template_id', 'version_number']);
        });

        Schema::table('project_templates', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('project_template_versions')->nullOnDelete();
        });

        Schema::create('learner_webpage_projects', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('preview_actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_template_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_allocation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('project_mode')->index();
            $table->string('status')->index();
            $table->unsignedInteger('current_revision_number')->default(0);
            $table->unsignedInteger('state_version')->default(1);
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('first_started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'status', 'updated_at']);
            $table->index(['project_template_version_id', 'status']);
            $table->index(['assignment_attempt_id', 'status']);
        });

        Schema::create('project_revisions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('learner_webpage_project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->longText('source_html');
            $table->longText('sanitised_html');
            $table->json('structural_representation')->nullable();
            $table->string('source_checksum', 64)->index();
            $table->string('sanitised_checksum', 64)->index();
            $table->string('validation_version')->default('html-validation-v1');
            $table->string('sanitiser_version')->default('html-sanitiser-v1');
            $table->string('validation_status')->index();
            $table->string('revision_type')->default('autosave')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['learner_webpage_project_id', 'revision_number'], 'project_revision_number_unique');
        });

        Schema::create('project_autosaves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learner_webpage_project_id')->constrained()->cascadeOnDelete();
            $table->uuid('autosave_uuid');
            $table->unsignedInteger('based_on_state_version');
            $table->unsignedInteger('resulting_state_version');
            $table->longText('bounded_source');
            $table->string('source_checksum', 64);
            $table->string('client_instance_id')->nullable()->index();
            $table->timestamp('saved_at');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['learner_webpage_project_id', 'autosave_uuid'], 'project_autosave_uuid_unique');
        });

        Schema::create('html_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('preview_actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('html_exercise_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('learner_webpage_project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_allocation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attempt_type')->default('practice');
            $table->string('status')->index();
            $table->string('input_mode')->default('guided_code');
            $table->unsignedInteger('state_version')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('first_interaction_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('active_duration_ms')->default(0);
            $table->unsignedInteger('assistance_count')->default(0);
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'status', 'updated_at']);
        });

        Schema::create('html_attempt_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('html_attempt_id')->constrained()->cascadeOnDelete();
            $table->string('response_type');
            $table->longText('bounded_response');
            $table->longText('sanitised_response')->nullable();
            $table->json('structural_response')->nullable();
            $table->string('input_method')->default('guided_code');
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('html_validation_results', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('html_attempt_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_revision_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('validity_status')->index();
            $table->unsignedInteger('required_rule_count')->default(0);
            $table->unsignedInteger('satisfied_rule_count')->default(0);
            $table->unsignedInteger('unsafe_item_count')->default(0);
            $table->unsignedInteger('syntax_issue_count')->default(0);
            $table->unsignedInteger('structure_issue_count')->default(0);
            $table->unsignedInteger('accessibility_issue_count')->default(0);
            $table->json('assistance_summary')->nullable();
            $table->json('result_summary');
            $table->string('calculation_version')->default('html-validation-v1');
            $table->string('result_checksum', 64)->index();
            $table->timestamp('completed_at');
            $table->timestamps();
        });

        Schema::create('html_requirement_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('html_validation_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('html_exercise_requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_identifier');
            $table->string('outcome')->index();
            $table->json('evidence_summary')->nullable();
            $table->string('safe_guidance_code')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('project_checkpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learner_webpage_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_revision_id')->nullable()->constrained()->nullOnDelete();
            $table->string('checkpoint_identifier');
            $table->string('status')->index();
            $table->foreignId('html_validation_result_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('progress_event_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['learner_webpage_project_id', 'checkpoint_identifier'], 'project_checkpoint_unique');
        });

        Schema::create('project_reviews', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('learner_webpage_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_revision_id')->constrained('project_revisions')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('review_status')->index();
            $table->json('rubric_result')->nullable();
            $table->text('child_feedback');
            $table->text('teacher_only_notes')->nullable();
            $table->json('requested_changes')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_showcase_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('learner_webpage_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_revision_id')->constrained('project_revisions')->restrictOnDelete();
            $table->string('visibility_scope')->default('private')->index();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('title_override')->nullable();
            $table->text('safe_description')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('assignment_items') && ! Schema::hasColumn('assignment_items', 'html_exercise_version_id')) {
            Schema::table('assignment_items', function (Blueprint $table): void {
                $table->foreignId('html_exercise_version_id')->nullable()->after('typing_exercise_version_id')->constrained()->nullOnDelete();
                $table->foreignId('project_template_version_id')->nullable()->after('html_exercise_version_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignment_items') && Schema::hasColumn('assignment_items', 'html_exercise_version_id')) {
            Schema::table('assignment_items', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('project_template_version_id');
                $table->dropConstrainedForeignId('html_exercise_version_id');
            });
        }

        Schema::dropIfExists('project_showcase_entries');
        Schema::dropIfExists('project_reviews');
        Schema::dropIfExists('project_checkpoints');
        Schema::dropIfExists('html_requirement_results');
        Schema::dropIfExists('html_validation_results');
        Schema::dropIfExists('html_attempt_responses');
        Schema::dropIfExists('html_attempts');
        Schema::dropIfExists('project_autosaves');
        Schema::dropIfExists('project_revisions');
        Schema::dropIfExists('learner_webpage_projects');

        Schema::table('project_templates', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('project_template_versions');
        Schema::dropIfExists('project_templates');

        Schema::table('html_exercises', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('html_exercise_requirements');
        Schema::dropIfExists('html_exercise_versions');
        Schema::dropIfExists('html_exercises');
        Schema::dropIfExists('html_tag_policies');
    }
};
