<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uniqueItems = [
            "Laptop",
            "Printer",
            "Support",
            "Network",
            "Monitor",
            "Inventory & Supplies",
            "Email",
            "Display & Projection",
            "CCTV",
            "Desktop",
            "Meetings & Events",
            "Procurement",
            "Scanner",
            "AV & Cabling",
            "Servers",
            "Internet",
            "Data & Backup",
            "Mobile/Tablet",
            "Applications & OS",
            "Accessories",
            "Accounts & Access",
            "Components",
            "Traveling & Visit"
        ];

        $now = now();

        foreach ($uniqueItems as $name) {
            DB::table('item_types')->updateOrInsert(
                ['name' => $name],
                [
                    'has_specification' => false,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

    }
}