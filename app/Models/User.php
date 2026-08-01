<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Support\AvatarUrl;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Attributes appended to model serialization.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
        'dashboard_route',
        'primary_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function childProfile(): HasOne
    {
        return $this->hasOne(ChildProfile::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_child_relationships', 'parent_user_id', 'child_user_id')
            ->using(ParentChildRelationship::class)
            ->withPivot([
                'relationship_type',
                'is_primary_contact',
                'can_manage_pin',
                'can_view_progress',
                'created_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_child_relationships', 'child_user_id', 'parent_user_id')
            ->using(ParentChildRelationship::class)
            ->withPivot([
                'relationship_type',
                'is_primary_contact',
                'can_manage_pin',
                'can_view_progress',
                'created_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function teachingClasses(): BelongsToMany
    {
        return $this->belongsToMany(LearningClass::class, 'class_teacher_assignments', 'teacher_user_id', 'class_id')
            ->using(ClassTeacherAssignment::class)
            ->withPivot([
                'is_primary_teacher',
                'role_label',
                'assigned_by_user_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(LearningClass::class, 'class_enrolments', 'child_user_id', 'class_id')
            ->using(ClassEnrollment::class)
            ->withPivot([
                'status',
                'is_primary_class',
                'enrolled_by_user_id',
                'enrolled_at',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function progressProfile(): HasOne
    {
        return $this->hasOne(LearnerProgressProfile::class, 'child_id');
    }

    public function badgeAwards(): HasMany
    {
        return $this->hasMany(BadgeAward::class, 'child_id');
    }

    public function isActiveAccount(): bool
    {
        return $this->deactivated_at === null;
    }

    public function primaryRole(): ?RoleName
    {
        foreach (RoleName::priority() as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function dashboardRouteName(): string
    {
        return $this->primaryRole()?->dashboardRoute() ?? 'home';
    }

    public function dashboardUrl(): string
    {
        return route($this->dashboardRouteName(), absolute: false);
    }

    public function getDashboardRouteAttribute(): string
    {
        return $this->dashboardUrl();
    }

    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->primaryRole()?->value;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatarPath = $this->profile?->avatar_path ?? $this->childProfile?->avatar_path ?? null;

        return AvatarUrl::resolve($avatarPath);
    }
}
