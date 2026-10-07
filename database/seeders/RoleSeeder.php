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
        $roles = ['admin', 'manager', 'helpdesk_agent'];

        foreach ($roles as $name){
            Role::firstOrCreate(['name' => $name]);
        }

    }
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