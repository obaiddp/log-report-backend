<?php

// namespace Database\Seeders;

// use App\Models\Role;
// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;

// class UserSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $adminPassword = env('INITIAL_ADMIN_PASSWORD');
//         $resourcePassword = env('INITIAL_RESOURCE_PASSWORD');

//         if (! $adminPassword || ! $resourcePassword) {
//             $this->command->error('Set INITIAL_ADMIN_PASSWORD and INITIAL_RESOURCE_PASSWORD in .env first.');
//             return;
//         }

//         $adminId = Role::where('name', 'admin')->value('id');
//         $managerId = Role::where('name', 'manager')->value('id');
//         $helpdeskAgentId = Role::where('name', 'helpdesk_agent')->value('id');


//         $users = [
//             ['name' => 'Mudassir Shahid', 'email' => 'mudassir.shahid@cef.org.pk', 'role_id' => $adminId, 'designation' => 'Senior Administrator', 'password' => $adminPassword],
//             ['name' => 'Muhammad Afaq', 'email' => 'afaq@cef.org.pk', 'role_id' => $managerId, 'designation' => 'Junior Administrator', 'password' => $resourcePassword],
//             ['name' => 'Haris Niaz Abbasi', 'email' => 'haris.niaz@cef.org.pk', 'role_id' => $helpdeskAgentId, 'designation' => 'Junior Administrator', 'password' => $resourcePassword],
//             ['name' => 'Zulfiqar Ahmed', 'email' => 'zulfiqar.ahmed@cef.org.pk', 'role_id' => $helpdeskAgentId, 'designation' => 'Senior Developer', 'password' => $resourcePassword],
//             ['name' => 'Obaid Ullah Zeb', 'email' => 'obaid.ullah@cef.org.pk', 'role_id' => $helpdeskAgentId, 'designation' => 'Assistant Developer', 'password' => $resourcePassword],
//         ];

//         foreach ($users as $u) {
//             DB::table('users')->insert([
//                 'name' => $u['name'],
//                 'email' => $u['email'],
//                 'password' => Hash::make($u['password']),
//                 'role_id' => $u['role_id'],
//                 'designation' => $u['designation'],
//                 'email_verified_at' => now(),
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ]);
//         }
//     }
// }

// ===================================================

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('INITIAL_ADMIN_PASSWORD');
        $resourcePassword = env('INITIAL_RESOURCE_PASSWORD');

        if (! $adminPassword || ! $resourcePassword) {
            $this->command->error(
                'Set INITIAL_ADMIN_PASSWORD and INITIAL_RESOURCE_PASSWORD in .env first.'
            );

            return;
        }

        $adminId = Role::where('name', 'admin')->value('id');
        $managerId = Role::where('name', 'manager')->value('id');
        $helpdeskAgentId = Role::where('name', 'helpdesk_agent')->value('id');

        // Resolve department IDs using stable department codes.
        $adminDepartmentId = DB::table('departments')
            ->where('code', 'admin-log')
            ->value('id');

        $ictDepartmentId = DB::table('departments')
            ->where('code', 'ict')
            ->value('id');

        $users = [
            [
                'name' => 'Mudassir Shahid',
                'email' => 'mudassir.shahid@cef.org.pk',
                'role_id' => $adminId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Senior Administrator',
                'password' => $adminPassword,
            ],
            [
                'name' => 'Muhammad Afaq',
                'email' => 'afaq@cef.org.pk',
                'role_id' => $managerId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Junior Administrator',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Haris Niaz Abbasi',
                'email' => 'haris.niaz@cef.org.pk',
                'role_id' => $helpdeskAgentId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Junior Administrator',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Zulfiqar Ahmed',
                'email' => 'zulfiqar.ahmed@cef.org.pk',
                'role_id' => $helpdeskAgentId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Senior Developer',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Obaid Ullah Zeb',
                'email' => 'obaid.ullah@cef.org.pk',
                'role_id' => $helpdeskAgentId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Assistant Developer',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Mudassir Shahid',
                'email' => 'mubarik@cef.org.pk',
                'role_id' => $adminId,
                'department_id' => $adminDepartmentId,
                'designation' => 'Admin Head',
                'password' => $resourcePassword,
            ],
        ];

        foreach ($users as $user) {
            if (
                ! $user['role_id'] ||
                ! $user['department_id']
            ) {
                throw new \RuntimeException(
                    "Missing role or department for {$user['email']}. "
                    . 'Check RoleSeeder and DepartmentSeeder.'
                );
            }

            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role_id' => $user['role_id'],
                    'department_id' => $user['department_id'],
                    'designation' => $user['designation'],
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
