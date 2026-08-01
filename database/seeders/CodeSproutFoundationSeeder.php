<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\AcademicCohort;
use App\Models\ApplicationSetting;
use App\Models\AuditLog;
use App\Models\ChildProfile;
use App\Models\ClassEnrollment;
use App\Models\ClassTeacherAssignment;
use App\Models\LearningClass;
use App\Models\ParentChildRelationship;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CodeSproutFoundationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->updateOrCreate(
                ['email' => 'admin@childsbridge.test'],
                [
                    'name' => 'Daniel Brooks',
                    'email_verified_at' => now(),
                    'password' => 'Password123!',
                    'deactivated_at' => null,
                ],
            );

            $teacher = User::query()->updateOrCreate(
                ['email' => 'teacher@childsbridge.test'],
                [
                    'name' => 'Maya Johnson',
                    'email_verified_at' => now(),
                    'password' => 'Password123!',
                    'deactivated_at' => null,
                ],
            );

            $parent = User::query()->updateOrCreate(
                ['email' => 'parent@childsbridge.test'],
                [
                    'name' => 'Grace Stone',
                    'email_verified_at' => now(),
                    'password' => 'Password123!',
                    'deactivated_at' => null,
                ],
            );

            $childOne = User::query()->updateOrCreate(
                ['name' => 'Amara Stone'],
                [
                    'email' => null,
                    'email_verified_at' => null,
                    'password' => 'Password123!',
                    'deactivated_at' => null,
                ],
            );

            $childTwo = User::query()->updateOrCreate(
                ['name' => 'Noah Stone'],
                [
                    'email' => null,
                    'email_verified_at' => null,
                    'password' => 'Password123!',
                    'deactivated_at' => null,
                ],
            );

            $admin->syncRoles(RoleName::Administrator->value);
            $teacher->syncRoles(RoleName::Teacher->value);
            $parent->syncRoles(RoleName::Parent->value);
            $childOne->syncRoles(RoleName::Child->value);
            $childTwo->syncRoles(RoleName::Child->value);

            UserProfile::query()->updateOrCreate(
                ['user_id' => $admin->id],
                [
                    'first_name' => 'Daniel',
                    'last_name' => 'Brooks',
                    'preferred_name' => 'Daniel',
                    'date_of_birth' => Carbon::create(1988, 4, 14),
                    'avatar_path' => null,
                    'notes' => 'Platform administrator for the CodeSprout development seed.',
                ],
            );

            UserProfile::query()->updateOrCreate(
                ['user_id' => $teacher->id],
                [
                    'first_name' => 'Maya',
                    'last_name' => 'Johnson',
                    'preferred_name' => 'Teacher Maya',
                    'date_of_birth' => Carbon::create(1990, 11, 2),
                    'avatar_path' => null,
                    'notes' => 'Sample classroom teacher.',
                ],
            );

            UserProfile::query()->updateOrCreate(
                ['user_id' => $parent->id],
                [
                    'first_name' => 'Grace',
                    'last_name' => 'Stone',
                    'preferred_name' => 'Grace',
                    'date_of_birth' => Carbon::create(1986, 7, 20),
                    'avatar_path' => null,
                    'notes' => 'Sample parent contact.',
                ],
            );

            UserProfile::query()->updateOrCreate(
                ['user_id' => $childOne->id],
                [
                    'first_name' => 'Amara',
                    'last_name' => 'Stone',
                    'preferred_name' => 'Amara',
                    'date_of_birth' => Carbon::create(2019, 6, 12),
                    'avatar_path' => 'assets/codesprout/original/CodeSprout-Avatar-Amara.png',
                    'notes' => 'First sample learner.',
                ],
            );

            UserProfile::query()->updateOrCreate(
                ['user_id' => $childTwo->id],
                [
                    'first_name' => 'Noah',
                    'last_name' => 'Stone',
                    'preferred_name' => 'Noah',
                    'date_of_birth' => Carbon::create(2019, 9, 18),
                    'avatar_path' => null,
                    'notes' => 'Second sample learner.',
                ],
            );

            TeacherProfile::query()->updateOrCreate(
                ['user_id' => $teacher->id],
                [
                    'staff_code' => 'TCH-1001',
                    'job_title' => 'Lead Learning Coach',
                    'subject_focus' => 'Computer readiness and early coding',
                    'notes' => 'Assigned to the sample class for phase 1.',
                ],
            );

            ChildProfile::query()->updateOrCreate(
                ['user_id' => $childOne->id],
                [
                    'learner_id' => 'CB-LEARN-1001',
                    'pin_mode' => 'numeric',
                    'pin_hash' => Hash::make('1234'),
                    'pin_hint' => 'Starts with 1 and ends with 4.',
                    'last_pin_verified_at' => null,
                    'pin_reset_required_at' => null,
                    'notes' => 'Primary sample learner.',
                ],
            );

            ChildProfile::query()->updateOrCreate(
                ['user_id' => $childTwo->id],
                [
                    'learner_id' => 'CB-LEARN-1002',
                    'pin_mode' => 'numeric',
                    'pin_hash' => Hash::make('2468'),
                    'pin_hint' => 'Even numbers only.',
                    'last_pin_verified_at' => null,
                    'pin_reset_required_at' => null,
                    'notes' => 'Secondary sample learner.',
                ],
            );

            $cohort = AcademicCohort::query()->updateOrCreate(
                ['academic_year' => '2026-2027'],
                [
                    'name' => 'CodeSprout Cohort 2026-2027',
                    'starts_on' => Carbon::create(2026, 9, 1),
                    'ends_on' => Carbon::create(2027, 7, 31),
                    'is_current' => true,
                    'notes' => 'Sample one-year cohort for development and tests.',
                ],
            );

            $class = LearningClass::query()->updateOrCreate(
                ['class_code' => 'CB-KEY-01'],
                [
                    'academic_cohort_id' => $cohort->id,
                    'name' => 'Keyboard Island',
                    'description' => 'Introductory keyboard world for the child-safe sample class.',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            );

            ClassTeacherAssignment::query()->updateOrCreate(
                [
                    'class_id' => $class->id,
                    'teacher_user_id' => $teacher->id,
                ],
                [
                    'is_primary_teacher' => true,
                    'role_label' => 'Lead teacher',
                    'assigned_by_user_id' => $admin->id,
                ],
            );

            ClassEnrollment::query()->updateOrCreate(
                [
                    'class_id' => $class->id,
                    'child_user_id' => $childOne->id,
                ],
                [
                    'status' => 'active',
                    'is_primary_class' => true,
                    'enrolled_by_user_id' => $admin->id,
                    'enrolled_at' => now(),
                ],
            );

            ClassEnrollment::query()->updateOrCreate(
                [
                    'class_id' => $class->id,
                    'child_user_id' => $childTwo->id,
                ],
                [
                    'status' => 'active',
                    'is_primary_class' => true,
                    'enrolled_by_user_id' => $admin->id,
                    'enrolled_at' => now(),
                ],
            );

            ParentChildRelationship::query()->updateOrCreate(
                [
                    'parent_user_id' => $parent->id,
                    'child_user_id' => $childOne->id,
                ],
                [
                    'relationship_type' => 'guardian',
                    'is_primary_contact' => true,
                    'can_manage_pin' => true,
                    'can_view_progress' => true,
                    'created_by_user_id' => $admin->id,
                ],
            );

            ParentChildRelationship::query()->updateOrCreate(
                [
                    'parent_user_id' => $parent->id,
                    'child_user_id' => $childTwo->id,
                ],
                [
                    'relationship_type' => 'guardian',
                    'is_primary_contact' => false,
                    'can_manage_pin' => true,
                    'can_view_progress' => true,
                    'created_by_user_id' => $admin->id,
                ],
            );

            $settings = [
                [
                    'key' => 'platform_name',
                    'setting_group' => 'general',
                    'value' => 'ChildsBridge CodeSprout',
                    'data_type' => 'string',
                    'is_public' => true,
                ],
                [
                    'key' => 'default_world',
                    'setting_group' => 'curriculum',
                    'value' => 'Keyboard Island',
                    'data_type' => 'string',
                    'is_public' => true,
                ],
                [
                    'key' => 'child_pin_length',
                    'setting_group' => 'child_access',
                    'value' => '4',
                    'data_type' => 'integer',
                    'is_public' => false,
                ],
                [
                    'key' => 'public_leaderboards_enabled',
                    'setting_group' => 'safety',
                    'value' => 'false',
                    'data_type' => 'boolean',
                    'is_public' => false,
                ],
                [
                    'key' => 'child_messaging_enabled',
                    'setting_group' => 'safety',
                    'value' => 'false',
                    'data_type' => 'boolean',
                    'is_public' => false,
                ],
                [
                    'key' => 'support_email',
                    'setting_group' => 'contact',
                    'value' => 'support@childsbridge.academy',
                    'data_type' => 'string',
                    'is_public' => true,
                ],
            ];

            foreach ($settings as $setting) {
                ApplicationSetting::query()->updateOrCreate(
                    ['key' => $setting['key']],
                    $setting,
                );
            }

            AuditLog::query()->updateOrCreate(
                [
                    'action' => 'seeded.foundation.initialised',
                    'subject_type' => LearningClass::class,
                    'subject_id' => $class->id,
                ],
                [
                    'actor_user_id' => $admin->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'CodeSproutSeeder',
                    'metadata' => [
                        'class_code' => $class->class_code,
                        'cohort' => $cohort->academic_year,
                    ],
                    'created_at' => now(),
                ],
            );

            AuditLog::query()->updateOrCreate(
                [
                    'action' => 'seeded.foundation.teacher_assigned',
                    'subject_type' => TeacherProfile::class,
                    'subject_id' => $teacher->teacherProfile?->id,
                ],
                [
                    'actor_user_id' => $admin->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'CodeSproutSeeder',
                    'metadata' => [
                        'teacher' => $teacher->name,
                        'class' => $class->name,
                    ],
                    'created_at' => now(),
                ],
            );
        });
    }
}
