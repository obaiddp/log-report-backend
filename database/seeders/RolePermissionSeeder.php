<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'super_admin' => Permission::pluck('name')->all(),
            'ict_manager' => [
                'manage_dashboard', 'dashboard_support_logs', 'dashboard_asset_inspections', 'user_performance',
                'manage_support_logs', 'manage_asset_inspections', 'create_asset_inspections',
                'manage_item_types', 'manage_issue_types', 'dashboard_department_view', 'dashboard_all_view',
            ],
            'ict_staff' => [
                'manage_dashboard', 'dashboard_support_logs', 'dashboard_asset_inspections',
                'manage_support_logs', 'manage_asset_inspections', 'create_asset_inspections',
            ],
            'admin_approver' => [
                'manage_dashboard', 'dashboard_asset_inspections', 'manage_asset_inspections', 'dashboard_department_view'
            ],
        ];

        foreach ($map as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }
            $role->permissions()->sync(Permission::whereIn('name', $permissionNames)->pluck('id'));
        }
    }   
}