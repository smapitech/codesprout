<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->unsignedTinyInteger('target_min_age')->nullable();
            $table->unsignedTinyInteger('target_max_age')->nullable();
            $table->unsignedSmallInteger('duration_weeks')->default(48);
            $table->unsignedTinyInteger('lessons_per_week')->default(3);
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('curriculum_worlds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedTinyInteger('world_number');
            $table->string('short_description')->nullable();
            $table->longText('story_description')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->string('theme_colour', 32)->nullable();
            $table->string('accent_colour', 32)->nullable();
            $table->string('icon_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->unsignedTinyInteger('estimated_weeks')->default(4);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['curriculum_id', 'slug']);
            $table->unique(['curriculum_id', 'world_number']);
            $table->unique(['curriculum_id', 'display_order']);
            $table->index(['curriculum_id', 'status']);
        });

        Schema::create('curriculum_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('curriculum_worlds')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->unsignedTinyInteger('week_number');
            $table->longText('description')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['world_id', 'slug']);
            $table->unique(['world_id', 'week_number']);
            $table->unique(['world_id', 'display_order']);
            $table->index(['world_id', 'status']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('curriculum_units')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->unsignedTinyInteger('lesson_number');
            $table->longText('description')->nullable();
            $table->longText('teacher_notes')->nullable();
            $table->longText('learner_objective')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(10);
            $table->string('difficulty_level')->default('introductory');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'slug']);
            $table->unique(['unit_id', 'lesson_number']);
            $table->unique(['unit_id', 'display_order']);
            $table->index(['unit_id', 'status']);
        });

        Schema::create('lesson_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('stage_type');
            $table->string('interaction_type');
            $table->longText('instruction_text');
            $table->longText('encouragement_text')->nullable();
            $table->longText('teacher_guidance')->nullable();
            $table->string('audio_instruction_path')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(5);
            $table->string('difficulty_level')->default('introductory');
            $table->unsignedTinyInteger('star_value')->default(5);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'slug']);
            $table->unique(['lesson_id', 'display_order']);
            $table->index(['lesson_id', 'status']);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->longText('description')->nullable();
            $table->longText('mastery_description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'status']);
        });

        Schema::create('lesson_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->unsignedTinyInteger('emphasis_level')->default(1);
            $table->timestamps();

            $table->unique(['lesson_id', 'skill_id']);
            $table->index(['skill_id', 'lesson_id']);
        });

        Schema::create('stage_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_stage_id')->constrained('lesson_stages')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lesson_stage_id', 'skill_id']);
            $table->index(['skill_id', 'lesson_stage_id']);
        });

        Schema::create('curriculum_world_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_world_id')->constrained('curriculum_worlds')->cascadeOnDelete();
            $table->foreignId('prerequisite_world_id')->constrained('curriculum_worlds')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['curriculum_world_id', 'prerequisite_world_id'], 'curriculum_world_prereq_unique');
            $table->index(['prerequisite_world_id', 'curriculum_world_id'], 'curriculum_world_prereq_reverse');
        });

        Schema::create('lesson_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('prerequisite_lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lesson_id', 'prerequisite_lesson_id'], 'lesson_prereq_unique');
            $table->index(['prerequisite_lesson_id', 'lesson_id'], 'lesson_prereq_reverse');
        });

        Schema::create('lesson_stage_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_stage_id')->constrained('lesson_stages')->cascadeOnDelete();
            $table->foreignId('prerequisite_stage_id')->constrained('lesson_stages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lesson_stage_id', 'prerequisite_stage_id'], 'lesson_stage_prereq_unique');
            $table->index(['prerequisite_stage_id', 'lesson_stage_id'], 'lesson_stage_prereq_reverse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_stage_prerequisites');
        Schema::dropIfExists('lesson_prerequisites');
        Schema::dropIfExists('curriculum_world_prerequisites');
        Schema::dropIfExists('stage_skill');
        Schema::dropIfExists('lesson_skill');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('lesson_stages');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('curriculum_units');
        Schema::dropIfExists('curriculum_worlds');
        Schema::dropIfExists('curricula');
    }
};
