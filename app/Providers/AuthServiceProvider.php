<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Assignment;
use App\Models\AssignmentAllocation;
use App\Models\AssignmentAttempt;
use App\Models\AssignmentVersion;
use App\Models\ChildProfile;
use App\Models\Curriculum;
use App\Models\CurriculumLesson;
use App\Models\CurriculumUnit;
use App\Models\CurriculumWorld;
use App\Models\HtmlExercise;
use App\Models\LearnerWebpageProject;
use App\Models\LearningClass;
use App\Models\LessonStage;
use App\Models\ParentChildRelationship;
use App\Models\ProjectTemplate;
use App\Models\Skill;
use App\Models\TypingExercise;
use App\Models\TypingSession;
use App\Models\User;
use App\Policies\AssignmentAllocationPolicy;
use App\Policies\AssignmentAttemptPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\AssignmentVersionPolicy;
use App\Policies\ChildProfilePolicy;
use App\Policies\CurriculumPolicy;
use App\Policies\HtmlExercisePolicy;
use App\Policies\LearnerWebpageProjectPolicy;
use App\Policies\LearningClassPolicy;
use App\Policies\ParentChildRelationshipPolicy;
use App\Policies\ProjectTemplatePolicy;
use App\Policies\TypingExercisePolicy;
use App\Policies\TypingSessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(static function (User $user): ?bool {
            return $user->hasRole(RoleName::Administrator->value) ? true : null;
        });

        Gate::policy(Curriculum::class, CurriculumPolicy::class);
        Gate::policy(CurriculumWorld::class, CurriculumPolicy::class);
        Gate::policy(CurriculumUnit::class, CurriculumPolicy::class);
        Gate::policy(CurriculumLesson::class, CurriculumPolicy::class);
        Gate::policy(LessonStage::class, CurriculumPolicy::class);
        Gate::policy(Skill::class, CurriculumPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(AssignmentVersion::class, AssignmentVersionPolicy::class);
        Gate::policy(AssignmentAllocation::class, AssignmentAllocationPolicy::class);
        Gate::policy(AssignmentAttempt::class, AssignmentAttemptPolicy::class);
        Gate::policy(ChildProfile::class, ChildProfilePolicy::class);
        Gate::policy(LearningClass::class, LearningClassPolicy::class);
        Gate::policy(ParentChildRelationship::class, ParentChildRelationshipPolicy::class);
        Gate::policy(TypingExercise::class, TypingExercisePolicy::class);
        Gate::policy(TypingSession::class, TypingSessionPolicy::class);
        Gate::policy(HtmlExercise::class, HtmlExercisePolicy::class);
        Gate::policy(ProjectTemplate::class, ProjectTemplatePolicy::class);
        Gate::policy(LearnerWebpageProject::class, LearnerWebpageProjectPolicy::class);
    }
}
