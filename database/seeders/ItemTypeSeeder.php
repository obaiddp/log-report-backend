<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\IssueType;
use Illuminate\Support\Facades\DB;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Desktop',
            'Laptop',
            'Printer',
            'Scanner',
            'Monitor',
            'Keyboard / Mouse',
            'Router',
            'Switch',
            'Access Point',
            'Server',
            'UPS',
            'Projector',
            'IP Phone',
            'CCTV',
            'Software',
            'Email Account',
            'Network / Internet',
            'Other',
        ];

        foreach ($names as $name) {
            DB::table('item_types')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]); 
        }

    }
}
