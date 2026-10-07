<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departments')->insert([
            [
                'code' => 'admin-log',
                'name' => 'Admin & Logistics',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ajk-sci',
                'name' => 'AJK Social Change Initiative',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'cef-online',
                'name' => 'CEF Online',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'corp-gov',
                'name' => 'Corporate Governance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'curriculum',
                'name' => 'Curriculum',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'finance',
                'name' => 'Finance & Accounts',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ict',
                'name' => 'ICT & Digitalization',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'iaep',
                'name' => 'Impact Assessment & Education Policy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'marketing',
                'name' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'hr',
                'name' => 'Human Resources',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'pr',
                'name' => 'PR & Advocacy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'proc-pub',
                'name' => 'Procurement & Publication',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'program',
                'name' => 'Program Management',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'rnd',
                'name' => 'Research & Development',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'rm',
                'name' => 'Resource Mobilization',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'sales',
                'name' => 'Sales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'scw',
                'name' => 'Supply Chain & Warehouse',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'training',
                'name' => 'Training',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}