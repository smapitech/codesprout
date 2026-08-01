<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LearningClassRequest;
use App\Http\Requests\Admin\SchoolConnectionRequest;
use App\Http\Requests\Admin\SchoolUserRequest;
use App\Http\Requests\Admin\UserStatusRequest;
use App\Models\AcademicCohort;
use App\Models\AuditLog;
use App\Models\ClassEnrollment;
use App\Models\ClassTeacherAssignment;
use App\Models\LearningClass;
use App\Models\ParentChildRelationship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SchoolManagementController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with(['roles:id,name', 'profile', 'teacherProfile', 'childProfile'])
            ->orderBy('name')
            ->limit(200)
            ->get();

        $classes = LearningClass::query()
            ->with(['cohort:id,name,academic_year', 'teachers.teacherProfile', 'learners.childProfile'])
            ->withCount(['teachers', 'learners'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/school/index', [
            'summary' => [
                'teachers' => $users->filter->hasRole(RoleName::Teacher->value)->count(),
                'parents' => $users->filter->hasRole(RoleName::Parent->value)->count(),
                'children' => $users->filter->hasRole(RoleName::Child->value)->count(),
                'activeClasses' => $classes->where('is_active', true)->count(),
                'childrenWithoutClass' => User::role(RoleName::Child->value)->whereDoesntHave('enrolledClasses')->count(),
                'childrenWithoutParent' => User::role(RoleName::Child->value)->whereDoesntHave('parents')->count(),
                'teachersWithoutClass' => User::role(RoleName::Teacher->value)->whereDoesntHave('teachingClasses')->count(),
            ],
            'users' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->primaryRole()?->value,
                'roleLabel' => $user->primaryRole()?->label(),
                'active' => $user->isActiveAccount(),
                'learnerId' => $user->childProfile?->learner_id,
                'staffCode' => $user->teacherProfile?->staff_code,
            ])->values(),
            'teachers' => $users->filter->hasRole(RoleName::Teacher->value)->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'staffCode' => $user->teacherProfile?->staff_code,
            ])->values(),
            'parents' => $users->filter->hasRole(RoleName::Parent->value)->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->values(),
            'children' => $users->filter->hasRole(RoleName::Child->value)->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'learnerId' => $user->childProfile?->learner_id,
            ])->values(),
            'classes' => $classes->map(fn (LearningClass $class): array => [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->class_code,
                'active' => $class->is_active,
                'cohort' => $class->cohort?->name,
                'teachersCount' => $class->teachers_count,
                'learnersCount' => $class->learners_count,
                'teachers' => $class->teachers->map(fn (User $teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'primary' => (bool) $teacher->pivot?->is_primary_teacher,
                ])->values(),
                'learners' => $class->learners->map(fn (User $child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'learnerId' => $child->childProfile?->learner_id,
                ])->values(),
            ]),
            'parentLinks' => ParentChildRelationship::query()
                ->with(['parent:id,name,email', 'child.childProfile'])
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (ParentChildRelationship $link): array => [
                    'id' => $link->id,
                    'parent' => $link->parent?->name,
                    'child' => $link->child?->name,
                    'learnerId' => $link->child?->childProfile?->learner_id,
                    'relationship' => $link->relationship_type,
                    'primary' => $link->is_primary_contact,
                ]),
            'cohorts' => AcademicCohort::query()->orderByDesc('is_current')->orderByDesc('academic_year')->get(['id', 'name', 'academic_year', 'is_current']),
            'roleOptions' => collect(RoleName::cases())->map(fn (RoleName $role): array => ['value' => $role->value, 'label' => $role->label()]),
            'actions' => [
                'createUser' => route('admin.school.users.store', absolute: false),
                'createClass' => route('admin.school.classes.store', absolute: false),
                'connect' => route('admin.school.connections.store', absolute: false),
            ],
        ]);
    }

    public function storeUser(SchoolUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = RoleName::from($data['role']);

        $user = DB::transaction(function () use ($data, $role, $request): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => $role === RoleName::Child ? Hash::make(Str::random(48)) : $data['password'],
            ]);
            if ($role !== RoleName::Child) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            $user->assignRole($role->value);
            $user->profile()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
            ]);

            if ($role === RoleName::Teacher) {
                $user->teacherProfile()->create([
                    'staff_code' => $data['staff_code'],
                    'job_title' => $data['job_title'] ?? 'Teacher',
                    'subject_focus' => $data['subject_focus'] ?? null,
                ]);
            }

            if ($role === RoleName::Child) {
                $user->childProfile()->create([
                    'learner_id' => strtoupper($data['learner_id']),
                    'pin_mode' => 'numeric',
                    'pin_hash' => Hash::make($data['pin']),
                ]);
            }

            $this->audit($request->user(), 'school.user.created', $user, ['role' => $role->value]);

            return $user;
        });

        return back()->with('status', "{$user->name} can now use the {$role->label()} account.");
    }

    public function storeClass(LearningClassRequest $request): RedirectResponse
    {
        $class = LearningClass::query()->create(array_merge($request->validated(), [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (LearningClass::query()->max('sort_order') ?? 0) + 1,
        ]));

        $this->audit($request->user(), 'school.class.created', $class, ['class_code' => $class->class_code]);

        return back()->with('status', "{$class->name} was created and is ready for enrolment.");
    }

    public function storeConnection(SchoolConnectionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $message = DB::transaction(function () use ($data, $request): string {
            return match ($data['connection_type']) {
                'teacher_class' => $this->connectTeacher($data, $request->user()),
                'child_class' => $this->enrolChild($data, $request->user()),
                'parent_child' => $this->connectParent($data, $request->user()),
            };
        });

        return back()->with('status', $message);
    }

    public function updateStatus(UserStatusRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()) && ! $request->boolean('active'), 422, 'You cannot deactivate your own administrator account.');

        $user->forceFill(['deactivated_at' => $request->boolean('active') ? null : now()])->save();
        $this->audit($request->user(), $request->boolean('active') ? 'school.user.activated' : 'school.user.deactivated', $user);

        return back()->with('status', $request->boolean('active') ? 'Account activated.' : 'Account access suspended.');
    }

    private function connectTeacher(array $data, User $actor): string
    {
        $teacher = User::role(RoleName::Teacher->value)->findOrFail($data['teacher_id']);
        $class = LearningClass::query()->findOrFail($data['class_id']);
        $primary = (bool) ($data['is_primary'] ?? false);

        if ($primary) {
            ClassTeacherAssignment::query()->where('class_id', $class->id)->update(['is_primary_teacher' => false]);
        }

        $class->teachers()->syncWithoutDetaching([$teacher->id => [
            'is_primary_teacher' => $primary,
            'role_label' => $data['role_label'] ?? 'Class teacher',
            'assigned_by_user_id' => $actor->id,
        ]]);
        $class->teachers()->updateExistingPivot($teacher->id, ['is_primary_teacher' => $primary, 'role_label' => $data['role_label'] ?? 'Class teacher']);
        $this->audit($actor, 'school.teacher.assigned', $class, ['teacher_id' => $teacher->id, 'primary' => $primary]);

        return "{$teacher->name} is now connected to {$class->name}.";
    }

    private function enrolChild(array $data, User $actor): string
    {
        $child = User::role(RoleName::Child->value)->findOrFail($data['child_id']);
        $class = LearningClass::query()->findOrFail($data['class_id']);
        $primary = (bool) ($data['is_primary'] ?? false);

        if ($primary) {
            ClassEnrollment::query()->where('child_user_id', $child->id)->update(['is_primary_class' => false]);
        }

        $class->learners()->syncWithoutDetaching([$child->id => [
            'status' => 'active',
            'is_primary_class' => $primary,
            'enrolled_by_user_id' => $actor->id,
            'enrolled_at' => now(),
        ]]);
        $class->learners()->updateExistingPivot($child->id, ['status' => 'active', 'is_primary_class' => $primary]);
        $this->audit($actor, 'school.child.enrolled', $class, ['child_id' => $child->id, 'primary' => $primary]);

        return "{$child->name} is now enrolled in {$class->name}.";
    }

    private function connectParent(array $data, User $actor): string
    {
        $parent = User::role(RoleName::Parent->value)->findOrFail($data['parent_id']);
        $child = User::role(RoleName::Child->value)->findOrFail($data['child_id']);
        $primary = (bool) ($data['is_primary'] ?? false);

        if ($primary) {
            ParentChildRelationship::query()->where('child_user_id', $child->id)->update(['is_primary_contact' => false]);
        }

        ParentChildRelationship::query()->updateOrCreate(
            ['parent_user_id' => $parent->id, 'child_user_id' => $child->id],
            [
                'relationship_type' => $data['relationship_type'] ?? 'guardian',
                'is_primary_contact' => $primary,
                'can_manage_pin' => true,
                'can_view_progress' => true,
                'created_by_user_id' => $actor->id,
            ],
        );
        $this->audit($actor, 'school.parent.connected', $child, ['parent_id' => $parent->id, 'primary' => $primary]);

        return "{$parent->name} can now view {$child->name}'s authorised parent dashboard.";
    }

    private function audit(?User $actor, string $action, object $subject, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
