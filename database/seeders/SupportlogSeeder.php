<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ItemType;
use App\Models\IssueType;
use App\Models\SupportLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupportLogSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve existing users by email, not database IDs.
        $admin = User::where('email', 'mudassir.shahid@cef.org.pk')
            ->firstOrFail();

        $worker = User::where('email', 'afaq@cef.org.pk')
            ->firstOrFail();

        // Resolve issue types.
        $hardware = IssueType::where('name', 'Hardware')->firstOrFail();
        $software = IssueType::where('name', 'Software')->firstOrFail();

        // Sample historical support logs.
        $logs = [
            [
                'ticket_number' => 'SL-SEED-0001',
                'issue_date' => '2026-04-08',
                'initiated_by' => 'CEO Sir',
                'department' => 'corp-gov',
                'item' => 'Printer',
                'issue_types' => ['Hardware'],
                'issue_details' => 'Power issue',
                'status' => 'outdoor_repairing',
            ],
            [
                'ticket_number' => 'SL-SEED-0002',
                'issue_date' => '2026-04-13',
                'initiated_by' => 'Mubarik Aslam',
                'department' => 'admin-log',
                'item' => 'Laptop',
                'issue_types' => ['Hardware'],
                'issue_details' => 'Inspection report',
                'status' => 'resolved',
            ],
            [
                'ticket_number' => 'SL-SEED-0003',
                'issue_date' => '2026-04-14',
                'initiated_by' => 'CEO Sir',
                'department' => 'corp-gov',
                'item' => 'Monitor',
                'issue_types' => ['Hardware'],
                'issue_details' => 'Connectivity issue',
                'status' => 'resolved',
            ],
            [
                'ticket_number' => 'SL-SEED-0004',
                'issue_date' => '2026-04-14',
                'initiated_by' => 'Mubarik Aslam',
                'department' => 'admin-log',
                'item' => 'Laptop',
                'issue_types' => ['Hardware', 'Software'],
                'issue_details' => 'Inspection and report generation',
                'status' => 'resolved',
            ],
        ];

        DB::transaction(function () use (
            $logs,
            $admin,
            $worker,
            $hardware,
            $software
        ) {
            foreach ($logs as $data) {
                $department = Department::where(
                    'code',
                    $data['department']
                )->firstOrFail();

                $itemType = ItemType::where(
                    'name',
                    $data['item']
                )->firstOrFail();

                $log = SupportLog::updateOrCreate(
                    ['ticket_number' => $data['ticket_number']],
                    [
                        'issue_date' => $data['issue_date'],
                        'initiated_by' => $data['initiated_by'],
                        'department_id' => $department->id,
                        'item_type_id' => $itemType->id,
                        'status' => $data['status'],
                        'issue_details' => $data['issue_details'],
                        'assigned_to' => $worker->id,
                        'created_by' => $admin->id,
                        'resolved_at' => $data['status'] === 'resolved'
                            ? $data['issue_date'] . ' 09:00:00'
                            : null,
                        'closed_at' => $data['status'] === 'closed'
                            ? $data['issue_date'] . ' 09:00:00'
                            : null,
                    ]
                );

                $issueTypeIds = collect($data['issue_types'])
                    ->map(fn ($name) => match ($name) {
                        'Hardware' => $hardware->id,
                        'Software' => $software->id,
                        default => throw new \InvalidArgumentException(
                            "Unknown issue type: {$name}"
                        ),
                    })
                    ->all();

                // Replace pivot links with the configured issue types.
                $log->issueTypes()->sync($issueTypeIds);
            }
        });

        $this->command->info('Support log sample data seeded successfully.');
    }
}
