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
        // // --- department permissions ---
        // DB::table('permissions')->insert([
        //     'name' => 'add-department',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('permissions')->insert([
        //     'name' => 'update-department',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('permissions')->insert([
        //     'name' => 'remove-department',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // // --- item_type permissions ---
        // DB::table('permissions')->insert([
        //     'name' => 'add-item_type',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('permissions')->insert([
        //     'name' => 'update-item_type',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // DB::table('permissions')->insert([
        //     'name' => 'remove-item_type',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // =========================================================
        // =========================================================

        $names = [
            'manage_departments',
            'manage_item_types',
            'manage_issue_types',
            'manage_users',
            'view_reports',
            'create_support_logs',
            'update_own_support_logs',
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