<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;
use Illuminate\Support\Facades\DB;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            'name' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            'name' => 'network_administrator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            'name' => 'software_developer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /*
    -> Permissions:
    manage_departments
    manage_item_types
    manage_issue_types
    manage_users
    manage_support_logs


    -> Roles
    Admin
    Manager
    helpdesk_agent

    -> RolePermissions
    
    
    */
}