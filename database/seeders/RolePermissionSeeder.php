<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Adjust role names to match what your RoleSeeder creates
        $map = [
            'admin' => ['manage_departments', 'manage_item_types', 'manage_issue_types'],
            // 'staff' => [],
        ];

        foreach ($map as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');

            // sync = no duplicates if you run it twice
            $role->permissions()->sync($permissionIds);
        }
    }
}