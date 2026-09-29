<?php

// namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// use Illuminate\Database\Seeder;

// use App\Models\User;
// use Illuminate\Support\Facades\DB;

// class UserSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         DB::table('users')->insert([
//             'name' => 'Mudassir Shahid',
//             'email' => 'mudassir.shahid@cef.org.pk',
//             'password' => bcrypt('mudassir123'),
//             'role_id' => 9,
//             'designation' => 'Senior Administrator',
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);

//         DB::table('users')->insert([
//             'name' => 'Haris Niaz Abbasi',
//             'email' => 'haris.niaz@cef.org.pk',
//             'password' => bcrypt('haris123'),
//             'role_id' => 10,
//             'designation' => 'Junior Administrator',
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);

//         DB::table('users')->insert([
//             'name' => 'Muhammad Afaq',
//             'email' => 'afaq@cef.org.pk',
//             'password' => bcrypt('afaq123'),
//             'role_id' => 10,
//             'designation' => 'Junior Administrator',
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);

//         DB::table('users')->insert([
//             'name' => 'Zulfiqar Ahmed',
//             'email' => 'zulfiqar.ahmed@cef.org.pk',
//             'password' => bcrypt('zulfiqar123'),
//             'role_id' => 11,
//             'designation' => 'Senior Developer',
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);

//         DB::table('users')->insert([
//             'name' => 'Obaid Ullah Zeb',
//             'email' => 'obaid.ullah@cef.org.pk',
//             'password' => bcrypt('obaid123'),
//             'role_id' => 11,
//             'designation' => 'Assistant Developer',
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);
//     }
// }

// ================================================================================================
// ================================================================================================
// ================================================================================================

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
        $networkAdminId = Role::where('name', 'network_administrator')->value('id');
        $softwareDevId = Role::where('name', 'software_developer')->value('id');

        $users = [
            ['name' => 'Mudassir Shahid', 'email' => 'mudassir.shahid@cef.org.pk', 'role_id' => $adminId, 'designation' => 'Senior Administrator', 'password' => $adminPassword],
            ['name' => 'Haris Niaz Abbasi', 'email' => 'haris.niaz@cef.org.pk', 'role_id' => $networkAdminId, 'designation' => 'Junior Administrator', 'password' => $resourcePassword],
            ['name' => 'Muhammad Afaq', 'email' => 'afaq@cef.org.pk', 'role_id' => $networkAdminId, 'designation' => 'Junior Administrator', 'password' => $resourcePassword],
            ['name' => 'Zulfiqar Ahmed', 'email' => 'zulfiqar.ahmed@cef.org.pk', 'role_id' => $softwareDevId, 'designation' => 'Senior Developer', 'password' => $resourcePassword],
            ['name' => 'Obaid Ullah Zeb', 'email' => 'obaid.ullah@cef.org.pk', 'role_id' => $softwareDevId, 'designation' => 'Assistant Developer', 'password' => $resourcePassword],
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