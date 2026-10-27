<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'manage_dashboard',
            'dashboard_support_logs',
            'dashboard_asset_inspections',
            'user_performance',
            'dashboard_department_view',
            'dashboard_all_view',

            'manage_support_logs',
            'manage_asset_inspections',
            'create_asset_inspections',

            'manage_departments',
            'manage_item_types',
            'manage_issue_types',

            'manage_users',             // super admin only
            'manage_role_permissions',  // super admin only
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
    }
}