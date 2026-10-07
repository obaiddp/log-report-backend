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
            $this->command->error('Set INITIAL_ADMIN_PASSWORD and INITIAL_RESOURCE_PASSWORD in .env first.');
            return;
        }

        $adminId = Role::where('name', 'admin')->value('id');
        $managerId = Role::where('name', 'manager')->value('id');
        $helpdeskAgentId = Role::where('name', 'helpdesk_agent')->value('id');


        $users = [
            ['name' => 'Mudassir Shahid', 'email' => 'mudassir.shahid@cef.org.pk', 'role_id' => $adminId, 'designation' => 'Senior Administrator', 'password' => $adminPassword],
            ['name' => 'Muhammad Afaq', 'email' => 'afaq@cef.org.pk', 'role_id' => $managerId, 'designation' => 'Junior Administrator', 'password' => $resourcePassword],
            ['name' => 'Haris Niaz Abbasi', 'email' => 'haris.niaz@cef.org.pk', 'role_id' => $helpdeskAgentId, 'designation' => 'Junior Administrator', 'password' => $resourcePassword],
            ['name' => 'Zulfiqar Ahmed', 'email' => 'zulfiqar.ahmed@cef.org.pk', 'role_id' => $helpdeskAgentId, 'designation' => 'Senior Developer', 'password' => $resourcePassword],
            ['name' => 'Obaid Ullah Zeb', 'email' => 'obaid.ullah@cef.org.pk', 'role_id' => $helpdeskAgentId, 'designation' => 'Assistant Developer', 'password' => $resourcePassword],
        ];

        foreach ($users as $u) {
            DB::table('users')->insert([
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make($u['password']),
                'role_id' => $u['role_id'],
                'designation' => $u['designation'],
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}