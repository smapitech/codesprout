<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('html_attempts', function (Blueprint $table): void {
            $table->foreignId('assignment_item_id')->nullable()->after('assignment_attempt_id')->constrained('assignment_items')->nullOnDelete();
            $table->unique(['assignment_attempt_id', 'assignment_item_id'], 'html_attempt_assignment_item_unique');
        });

        Schema::table('learner_webpage_projects', function (Blueprint $table): void {
            $table->foreignId('assignment_item_id')->nullable()->after('assignment_attempt_id')->constrained('assignment_items')->nullOnDelete();
            $table->unique(['assignment_attempt_id', 'assignment_item_id'], 'web_project_assignment_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('learner_webpage_projects', function (Blueprint $table): void {
            $table->dropUnique('web_project_assignment_item_unique');
            $table->dropConstrainedForeignId('assignment_item_id');
        });
        Schema::table('html_attempts', function (Blueprint $table): void {
            $table->dropUnique('html_attempt_assignment_item_unique');
            $table->dropConstrainedForeignId('assignment_item_id');
        });
    }
};
