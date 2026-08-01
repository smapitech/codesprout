<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'access admin dashboard',
            'manage system settings',
            'view audit logs',
            'view curriculum',
            'manage curriculum',
            'manage curriculum content',
            'reorder curriculum content',
            'publish curriculum content',
            'archive curriculum content',
            'duplicate curriculum content',
            'import curriculum',
            'export curriculum',
            'preview curriculum',
            'manage skills',
            'access teacher dashboard',
            'view published curriculum',
            'manage class assignments',
            'view assigned learners',
            'access parent dashboard',
            'view child progress',
            'manage child pins',
            'access child dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            RoleName::Administrator->value => $permissions,
            RoleName::Teacher->value => [
                'access teacher dashboard',
                'view curriculum',
                'view published curriculum',
                'manage class assignments',
                'view assigned learners',
                'preview curriculum',
            ],
            RoleName::Parent->value => [
                'access parent dashboard',
                'view child progress',
                'manage child pins',
            ],
            RoleName::Child->value => [
                'access child dashboard',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
