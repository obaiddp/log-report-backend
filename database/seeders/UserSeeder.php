<?php

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

        $superAdminId = Role::where('name', 'super_admin')->value('id');
        $managerId    = Role::where('name', 'ict_manager')->value('id');
        $staffId      = Role::where('name', 'ict_staff')->value('id');
        $approverId   = Role::where('name', 'admin_approver')->value('id');


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
                'role_id' => $superAdminId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Senior Network Administrator',
                'password' => $adminPassword,
            ],
            [
                'name' => 'Muhammad Afaq',
                'email' => 'afaq@cef.org.pk',
                'role_id' => $staffId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Assistant IT Officer',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Haris Hussain',
                'email' => 'harishussain@cef.org.pk',
                'role_id' => $staffId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Assistant IT Officer',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Zulfiqar Ahmed',
                'email' => 'zulfiqar.ahmed@cef.org.pk',
                'role_id' => $managerId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Assistant Developer',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Obaid Ullah Zeb',
                'email' => 'obaid.ullah@cef.org.pk',
                'role_id' => $staffId,
                'department_id' => $ictDepartmentId,
                'designation' => 'Assistant Developer',
                'password' => $resourcePassword,
            ],

            [
                'name' => 'Mubarik Aslam',
                'email' => 'mubarik@cef.org.pk',
                'role_id' => $approverId,
                'department_id' => $adminDepartmentId,
                'designation' => 'Admin Executive',
                'password' => $resourcePassword,
            ],
            [
                'name' => 'Muhammad Shaheer Ur Rehman',
                'email' => 'shaheer@cef.org.pk',
                'role_id' => $approverId,
                'department_id' => $adminDepartmentId,
                'designation' => 'Associate Manager',
                'password' => $resourcePassword,
            ]
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
