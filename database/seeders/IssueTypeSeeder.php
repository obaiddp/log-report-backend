<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\IssueType;
use Illuminate\Support\Facades\DB;

class IssueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Hardware',
            'Software',
            'Network'
        ];

        foreach ($names as $name) {
            DB::table('issue_types')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]); 
        }
    }
}
