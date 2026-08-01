<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_definitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category', 60)->index();
            $table->string('game_type', 80)->index();
            $table->longText('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('visibility', 40)->default('platform')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('game_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_definition_id')->constrained('game_definitions')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('configuration');
            $table->json('instruction_content')->nullable();
            $table->json('difficulty_configuration')->nullable();
            $table->json('supported_input_methods')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['game_definition_id', 'version_number']);
            $table->index(['game_definition_id', 'status']);
        });

        Schema::table('game_definitions', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('game_versions')->nullOnDelete();
        });

        Schema::table('assignment_items', function (Blueprint $table): void {
            $table->foreignId('game_version_id')->nullable()->after('assignment_version_id')->constrained('game_versions')->nullOnDelete();
        });

        Schema::create('game_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_version_id')->constrained('game_versions')->restrictOnDelete();
            $table->foreignId('lesson_stage_id')->nullable()->constrained('lesson_stages')->nullOnDelete();
            $table->foreignId('assignment_allocation_id')->nullable()->constrained('assignment_allocations')->cascadeOnDelete();
            $table->foreignId('assignment_attempt_id')->nullable()->constrained('assignment_attempts')->cascadeOnDelete();
            $table->foreignId('assignment_item_id')->nullable()->constrained('assignment_items')->nullOnDelete();
            $table->string('status', 30)->default('ready')->index();
            $table->string('difficulty', 30)->default('slow')->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('abandoned_at')->nullable()->index();
            $table->string('client_session_identifier')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->json('rounds')->nullable();
            $table->json('progress_data')->nullable();
            $table->unsignedInteger('current_round')->default(1);
            $table->timestamps();

            $table->index(['child_id', 'status']);
            $table->index(['game_version_id', 'status']);
            $table->index(['assignment_attempt_id', 'status']);
            $table->unique(['child_id', 'game_version_id', 'client_session_identifier'], 'game_child_client_unique');
        });

        Schema::create('game_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_session_id')->unique()->constrained('game_sessions')->cascadeOnDelete();
            $table->unsignedInteger('correct_actions')->default(0);
            $table->unsignedInteger('incorrect_actions')->default(0);
            $table->unsignedInteger('missed_actions')->default(0);
            $table->unsignedInteger('total_actions')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->unsignedInteger('completion_time')->default(0);
            $table->unsignedInteger('average_response_time')->nullable();
            $table->unsignedInteger('hints_used')->default(0);
            $table->unsignedInteger('assistance_used')->default(0);
            $table->json('raw_metrics')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('maximum_score', 8, 2)->default(0);
            $table->string('completion_status', 40)->default('partial')->index();
            $table->timestamp('calculated_at')->index();
            $table->boolean('released_to_parent')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('game_session_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            $table->json('round_data');
            $table->json('response_data')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('status', 30)->default('ready')->index();
            $table->timestamps();

            $table->unique(['game_session_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_rounds');
        Schema::dropIfExists('game_results');
        Schema::dropIfExists('game_sessions');
        Schema::table('assignment_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_version_id');
        });
        Schema::table('game_definitions', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('game_versions');
        Schema::dropIfExists('game_definitions');
    }
};
