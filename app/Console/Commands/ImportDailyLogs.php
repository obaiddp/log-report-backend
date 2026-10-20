<?php

// namespace App\Console\Commands;

// use App\Models\Department;
// use App\Models\IssueType;
// use App\Models\ItemType;
// use App\Models\SupportLog;
// use App\Models\User;
// use Carbon\Carbon;
// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;
// use Throwable;

// #[\Illuminate\Console\Attributes\Signature(
//     'app:import-daily-logs
//     {--department= : Existing department ID}
//     {--created-by= : Existing user ID who created the imported records}
//     {--file= : Optional absolute path to the CSV file}'
// )]
// #[\Illuminate\Console\Attributes\Description(
//     'Import historical daily support logs from CSV'
// )]
// class ImportDailyLogs extends Command
// {
//     public function handle(): int
//     {
//         $departmentId = (int) $this->option('department');
//         $createdById = (int) $this->option('created-by');

//         if (!Department::whereKey($departmentId)->exists()) {
//             $this->error('Provide a valid --department ID.');
//             return self::FAILURE;
//         }

//         if (!User::whereKey($createdById)->exists()) {
//             $this->error('Provide a valid --created-by user ID.');
//             return self::FAILURE;
//         }

//         $path = $this->option('file')
//             ?: storage_path('app/private/imports/daily_logs.csv');

//         if (!is_file($path) || !is_readable($path)) {
//             $this->error("CSV file not found or unreadable: {$path}");
//             return self::FAILURE;
//         }

//         $handle = fopen($path, 'r');

//         if ($handle === false) {
//             $this->error('Could not open the CSV file.');
//             return self::FAILURE;
//         }

//         $imported = 0;
//         $skipped = 0;
//         $failed = 0;
//         $rowNumber = 0;

//         try {
//             while (($row = fgetcsv($handle)) !== false) {
//                 // Ignore completely empty rows.
//                 if ($row === [null] || $row === []) {
//                     continue;
//                 }

//                 $row = array_map(
//                     fn ($value) => trim((string) ($value ?? '')),
//                     $row
//                 );

//                 if (count(array_filter($row, fn ($v) => $v !== '')) === 0) {
//                     continue;
//                 }

//                 // Your CSV has no header row.
//                 // The first seven columns contain the relevant data.
//                 $rowNumber++;
//                 $row = array_pad($row, 7, '');

//                 [
//                     $date,
//                     $initiatedBy,
//                     $itemName,
//                     $issueTypeName,
//                     $issueDetails,
//                     $status,
//                     $workedBy,
//                 ] = array_slice($row, 0, 7);

//                 // Stable identifiers prevent duplicate imports on reruns,
//                 // provided the CSV's row order remains unchanged.
//                 $ticketNumber = sprintf(
//                     'HIST-DAILY-%06d',
//                     $rowNumber
//                 );

//                 if (SupportLog::where(
//                     'ticket_number',
//                     $ticketNumber
//                 )->exists()) {
//                     $skipped++;
//                     continue;
//                 }

//                 try {
//                     if (
//                         !$date ||
//                         !$initiatedBy ||
//                         !$itemName ||
//                         !$issueTypeName ||
//                         !$issueDetails ||
//                         !$status ||
//                         !$workedBy
//                     ) {
//                         throw new \RuntimeException(
//                             'One or more required CSV fields are empty.'
//                         );
//                     }

//                     $issueDate = Carbon::createFromFormat(
//                         '!d/m/Y',
//                         $date
//                     )->format('Y-m-d');

//                     $item = ItemType::whereRaw(
//                         'LOWER(TRIM(name)) = ?',
//                         [mb_strtolower($itemName)]
//                     )->first();

//                     if (!$item) {
//                         throw new \RuntimeException(
//                             "Item type not found: {$itemName}"
//                         );
//                     }

//                     $assignedUser = User::whereRaw(
//                         'LOWER(TRIM(name)) = ?',
//                         [mb_strtolower($workedBy)]
//                     )->first();

//                     if (!$assignedUser) {
//                         throw new \RuntimeException(
//                             "Assigned user not found: {$workedBy}"
//                         );
//                     }

//                     // Match the exact issue type first.
//                     $issueType = IssueType::whereRaw(
//                         'LOWER(TRIM(name)) = ?',
//                         [mb_strtolower($issueTypeName)]
//                     )->first();

//                     if ($issueType) {
//                         $issueTypeIds = [$issueType->id];
//                     } elseif (
//                         mb_strtolower($issueTypeName)
//                         === 'hardware & software'
//                     ) {
//                         // If no combined type exists, use both types.
//                         $issueTypeIds = IssueType::whereIn(
//                             DB::raw('LOWER(TRIM(name))'),
//                             ['hardware', 'software']
//                         )->pluck('id')->all();

//                         if (count($issueTypeIds) !== 2) {
//                             throw new \RuntimeException(
//                                 'Hardware & Software requires either an '
//                                 . 'exact combined issue type or both '
//                                 . 'Hardware and Software issue types.'
//                             );
//                         }
//                     } else {
//                         throw new \RuntimeException(
//                             "Issue type not found: {$issueTypeName}"
//                         );
//                     }

//                     DB::transaction(function () use (
//                         $ticketNumber,
//                         $issueDate,
//                         $initiatedBy,
//                         $item,
//                         $status,
//                         $issueDetails,
//                         $assignedUser,
//                         $departmentId,
//                         $createdById,
//                         $issueTypeIds
//                     ) {
//                         $log = SupportLog::create([
//                             'ticket_number' => $ticketNumber,
//                             'issue_date' => $issueDate,
//                             'initiated_by' => $initiatedBy,
//                             'department_id' => $departmentId,
//                             'item_type_id' => $item->id,
//                             'status' => $status,
//                             'issue_details' => $issueDetails,
//                             'assigned_to' => $assignedUser->id,
//                             'created_by' => $createdById,
//                         ]);

//                         $log->issueTypes()->sync($issueTypeIds);
//                     });

//                     $imported++;
//                     $this->info(
//                         "Imported {$ticketNumber}: {$initiatedBy}"
//                     );
//                 } catch (Throwable $e) {
//                     $failed++;

//                     $this->warn(
//                         "CSV data row {$rowNumber} failed: "
//                         . $e->getMessage()
//                     );
//                 }
//             }
//         } finally {
//             fclose($handle);
//         }

//         $this->newLine();
//         $this->info("Imported: {$imported}");
//         $this->info("Skipped (already imported): {$skipped}");
//         $this->warn("Failed: {$failed}");

//         return $failed > 0 ? self::FAILURE : self::SUCCESS;
//     }
// }