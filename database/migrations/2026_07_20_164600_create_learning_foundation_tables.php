<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('academic_year')->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_current', 'academic_year']);
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_cohort_id')->constrained('academic_cohorts')->restrictOnDelete();
            $table->string('class_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academic_cohort_id', 'is_active']);
        });

        Schema::create('class_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary_teacher')->default(false);
            $table->string('role_label')->nullable();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'teacher_user_id']);
            $table->index(['teacher_user_id', 'class_id']);
        });

        Schema::create('class_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('child_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->boolean('is_primary_class')->default(true);
            $table->foreignId('enrolled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['class_id', 'child_user_id']);
            $table->index(['child_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrolments');
        Schema::dropIfExists('class_teacher_assignments');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('academic_cohorts');
    }
};
