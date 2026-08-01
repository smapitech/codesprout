<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('staff_code')->unique();
            $table->string('job_title')->nullable();
            $table->string('subject_focus')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('child_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('learner_id')->unique();
            $table->string('pin_mode', 20)->default('numeric');
            $table->string('pin_hash');
            $table->string('pin_hint')->nullable();
            $table->timestamp('last_pin_verified_at')->nullable();
            $table->timestamp('pin_reset_required_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('parent_child_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('child_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship_type')->default('guardian');
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_manage_pin')->default(true);
            $table->boolean('can_view_progress')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['parent_user_id', 'child_user_id']);
            $table->index(['child_user_id', 'parent_user_id']);
        });

        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('setting_group')->index();
            $table->longText('value')->nullable();
            $table->string('data_type', 30)->default('string');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('application_settings');
        Schema::dropIfExists('parent_child_relationships');
        Schema::dropIfExists('child_profiles');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('user_profiles');
    }
};
