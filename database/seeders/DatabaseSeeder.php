<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,   // after roles + permissions
            DepartmentSeeder::class,
            UserSeeder::class,
            ItemTypeSeeder::class,
            IssueTypeSeeder::class,

            // SupportLogSeeder::class,
        ]);
    }   
}