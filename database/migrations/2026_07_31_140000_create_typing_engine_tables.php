<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('typing_difficulty_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('difficulty_order')->default(1);
            $table->json('configuration');
            $table->string('status')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('typing_exercises', function (Blueprint $table): void {
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
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('typing_exercise_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('typing_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('typing_difficulty_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->json('content_configuration');
            $table->string('case_sensitive')->default('case_insensitive');
            $table->string('backspace_policy')->default('allowed');
            $table->string('correction_policy')->default('allowed');
            $table->string('input_method_policy')->default('any');
            $table->json('timer_configuration')->nullable();
            $table->json('completion_criteria');
            $table->decimal('accuracy_requirement', 5, 2)->default(0);
            $table->decimal('speed_requirement', 6, 2)->nullable();
            $table->json('assistance_configuration')->nullable();
            $table->json('adaptive_configuration')->nullable();
            $table->string('status')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->string('content_checksum', 64)->index();
            $table->timestamps();

            $table->unique(['typing_exercise_id', 'version_number']);
        });

        Schema::table('typing_exercises', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('typing_exercise_versions')->nullOnDelete();
        });

        Schema::create('typing_content_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('typing_exercise_version_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->text('prompt_text');
            $table->text('expected_text');
            $table->text('display_text')->nullable();
            $table->text('normalised_expected_text');
            $table->string('audio_path')->nullable();
            $table->string('image_path')->nullable();
            $table->json('target_keys')->nullable();
            $table->unsignedInteger('difficulty_order')->default(1);
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['typing_exercise_version_id', 'display_order']);
        });

        Schema::create('typing_exercise_skill', function (Blueprint $table): void {
            $table->foreignId('typing_exercise_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('emphasis_level')->default('primary');
            $table->timestamps();

            $table->primary(['typing_exercise_version_id', 'skill_id']);
        });

        Schema::create('typing_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('preview_actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('typing_exercise_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_allocation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_type')->default('practice')->index();
            $table->string('input_method')->default('unknown')->index();
            $table->string('keyboard_layout')->default('qwerty');
            $table->string('status')->index();
            $table->unsignedInteger('current_item_position')->default(0);
            $table->unsignedInteger('state_version')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('first_input_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('active_duration_ms')->default(0);
            $table->unsignedBigInteger('paused_duration_ms')->default(0);
            $table->unsignedInteger('last_event_sequence')->default(0);
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'status', 'updated_at']);
            $table->index(['typing_exercise_version_id', 'status']);
            $table->index(['assignment_attempt_id', 'status']);
        });

        Schema::create('typing_event_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('typing_session_id')->constrained()->cascadeOnDelete();
            $table->uuid('batch_uuid');
            $table->unsignedInteger('first_sequence');
            $table->unsignedInteger('last_sequence');
            $table->unsignedInteger('event_count');
            $table->timestamp('received_at');
            $table->string('payload_checksum', 64);
            $table->string('processing_status')->default('processed')->index();
            $table->timestamps();

            $table->unique(['typing_session_id', 'batch_uuid']);
        });

        Schema::create('typing_input_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('typing_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('typing_content_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->unsignedInteger('character_position')->nullable();
            $table->string('event_type');
            $table->string('expected_character', 20)->nullable();
            $table->string('entered_character', 20)->nullable();
            $table->string('normalised_character', 20)->nullable();
            $table->string('correctness_state')->nullable();
            $table->unsignedInteger('correction_sequence')->nullable();
            $table->string('input_method')->default('unknown');
            $table->json('modifier_state')->nullable();
            $table->unsignedInteger('elapsed_offset_ms')->default(0);
            $table->timestamp('server_received_at');
            $table->timestamp('retained_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['typing_session_id', 'sequence_number']);
            $table->index(['typing_session_id', 'typing_content_item_id']);
        });

        Schema::create('typing_results', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('typing_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('typing_exercise_version_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('expected_character_count')->default(0);
            $table->unsignedInteger('entered_character_count')->default(0);
            $table->unsignedInteger('total_keystrokes')->default(0);
            $table->unsignedInteger('correct_first_attempts')->default(0);
            $table->unsignedInteger('incorrect_first_attempts')->default(0);
            $table->unsignedInteger('corrected_errors')->default(0);
            $table->unsignedInteger('uncorrected_errors')->default(0);
            $table->unsignedInteger('omitted_characters')->default(0);
            $table->unsignedInteger('extra_characters')->default(0);
            $table->unsignedInteger('case_errors')->default(0);
            $table->unsignedInteger('spacing_errors')->default(0);
            $table->unsignedInteger('punctuation_errors')->default(0);
            $table->unsignedInteger('backspace_count')->default(0);
            $table->unsignedInteger('assistance_count')->default(0);
            $table->unsignedInteger('prompt_replay_count')->default(0);
            $table->unsignedBigInteger('active_duration_ms')->default(0);
            $table->decimal('characters_per_minute', 8, 2)->nullable();
            $table->decimal('gross_words_per_minute', 8, 2)->nullable();
            $table->decimal('adjusted_words_per_minute', 8, 2)->nullable();
            $table->decimal('first_attempt_accuracy', 6, 2)->default(0);
            $table->decimal('final_text_accuracy', 6, 2)->default(0);
            $table->decimal('completion_percentage', 6, 2)->default(0);
            $table->string('validity_status')->index();
            $table->timestamp('completed_at');
            $table->string('calculation_version')->default('typing-metrics-v1');
            $table->string('result_checksum', 64)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'completed_at']);
            $table->index(['typing_exercise_version_id', 'completed_at']);
        });

        Schema::create('typing_key_statistics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->string('key_identifier');
            $table->string('keyboard_layout')->default('qwerty');
            $table->string('input_method')->default('unknown');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('first_attempt_correct')->default(0);
            $table->unsignedInteger('corrected_attempts')->default(0);
            $table->decimal('recent_accuracy', 6, 2)->default(0);
            $table->decimal('highest_supported_accuracy', 6, 2)->default(0);
            $table->unsignedInteger('average_response_time_ms')->nullable();
            $table->string('mastery_label')->default('discovering');
            $table->timestamp('last_practised_at')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['child_id', 'key_identifier', 'keyboard_layout', 'input_method'], 'typing_key_stats_unique');
        });

        Schema::create('typing_progress_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('overall_accuracy', 6, 2)->default(0);
            $table->decimal('first_attempt_accuracy', 6, 2)->default(0);
            $table->decimal('final_text_accuracy', 6, 2)->default(0);
            $table->decimal('characters_per_minute', 8, 2)->nullable();
            $table->decimal('gross_words_per_minute', 8, 2)->nullable();
            $table->unsignedInteger('practised_keys')->default(0);
            $table->unsignedInteger('confident_keys')->default(0);
            $table->unsignedInteger('words_completed')->default(0);
            $table->unsignedInteger('sentences_completed')->default(0);
            $table->unsignedInteger('practice_minutes')->default(0);
            $table->json('input_method_summary')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['child_id', 'snapshot_date']);
        });

        if (Schema::hasTable('assignment_items') && ! Schema::hasColumn('assignment_items', 'typing_exercise_version_id')) {
            Schema::table('assignment_items', function (Blueprint $table): void {
                $table->foreignId('typing_exercise_version_id')->nullable()->after('game_version_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignment_items') && Schema::hasColumn('assignment_items', 'typing_exercise_version_id')) {
            Schema::table('assignment_items', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('typing_exercise_version_id');
            });
        }

        Schema::dropIfExists('typing_progress_snapshots');
        Schema::dropIfExists('typing_key_statistics');
        Schema::dropIfExists('typing_results');
        Schema::dropIfExists('typing_input_events');
        Schema::dropIfExists('typing_event_batches');
        Schema::dropIfExists('typing_sessions');
        Schema::dropIfExists('typing_exercise_skill');

        Schema::table('typing_exercises', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('typing_content_items');
        Schema::dropIfExists('typing_exercise_versions');
        Schema::dropIfExists('typing_exercises');
        Schema::dropIfExists('typing_difficulty_profiles');
    }
};
