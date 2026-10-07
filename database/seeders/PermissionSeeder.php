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
            'manage_users',
            'manage_roles',
            'user_performance',
            
            'manage_departments',
            'manage_item_types',
            'manage_issue_types',
            
            'create_support_logs',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $all = Permission::pluck('id');
        $starter = Permission::whereIn('name', ['create_support_logs', 'update_own_support_logs'])->pluck('id');


        Role::where('name', 'admin')->first()?->permissions()->sync($all);
        Role::where('name', 'network_administrator')->first()?->permissions()->sync($starter);
        Role::where('name', 'software_developer')->first()?->permissions()->sync($starter);
    }
}

/*
======= Permissions to seed =======

manage_user
user_performance
manage_role
manage_departments
manage_item_type
manage_issue_type
create_support_logs

*/