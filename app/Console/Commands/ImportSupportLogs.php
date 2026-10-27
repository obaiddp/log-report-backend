<?php

namespace App\Console\Commands;

use App\Models\SupportLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportSupportLogs extends Command
{
    protected $signature = 'import:support-logs
        {file : Path to the CSV file}
        {--created-by=mudassir.shahid@cef.org.pk : Fallback creator when a row has no worker}
        {--dry-run : Check the file and show a summary, but save nothing}
        {--collapse-duplicates : Treat identical rows inside the file as one}';

    protected $description = 'Import old support logs from a CSV file (all-or-nothing)';

    // CSV header (letters only, lowercase) => key used below
    private const COLUMNS = [
        'date'        => 'date',
        'initiatedby' => 'initiated_by',
        'department'  => 'department',
        'itemasset'   => 'item',
        'issuetype'   => 'issue_type',
        'issue'       => 'details',
        'status'      => 'status',
        'workedby'    => 'worker',
    ];

    
    private const STATUS_MAP = [
        'resolved'          => 'resolved',
        'solved'            => 'resolved',
        'closed'            => 'closed',
        'completed'         => 'resolved',
        'outdoor_repairing' => 'outdoor_repairing',
        'outdoor'           => 'outdoor_repairing',
        'indoor_repairing'  => 'indoor_repairing',
        'indoor'            => 'indoor_repairing',
        'in_progress'       => 'in_progress',
    ];


    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        $fallback = DB::table('users')->where('email', $this->option('created-by'))->first();
        if (! $fallback) {
            $this->error('Fallback creator not found: ' . $this->option('created-by'));
            return self::FAILURE;
        }

        $departments = $this->lookup('departments', 'code');
        $deptByName  = $this->lookup('departments', 'name');
        $items       = $this->lookup('item_types', 'name');
        $issueTypes  = $this->lookup('issue_types', 'name');
        $users       = $this->lookup('users', 'name');

        // ---- read the CSV ----
        $handle = fopen($path, 'r');
        $firstLine = (string) fgets($handle);
        rewind($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter) ?: [];
        if ($header) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]); // Excel BOM
        }

        $idx = [];
        foreach ($header as $i => $name) {
            $key = preg_replace('/[^a-z]/', '', strtolower((string) $name));
            if (isset(self::COLUMNS[$key])) {
                $idx[self::COLUMNS[$key]] = $i;
            }
        }
        $missing = array_diff(array_values(self::COLUMNS), array_keys($idx));
        if ($missing) {
            $this->error('Missing columns in the header row: ' . implode(', ', $missing));
            return self::FAILURE;
        }

        $rows = [];
        $errors = [];
        $line = 1;

        while (($raw = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($raw, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // empty row
            }

            $get = fn (string $k) => $this->clean($raw[$idx[$k]] ?? '');
            $e = [];

            
            // Date formats: 8/4/2026, 08/04/2026, 8-4-2026, or 2026-04-08
            $date = null;
            $dateText = $get('date');

            if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $dateText, $m)) {
                $day = (int) $m[1];
                $month = (int) $m[2];
                $year = (int) $m[3];
            } elseif (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $dateText, $m)) {
                $year = (int) $m[1];
                $month = (int) $m[2];
                $day = (int) $m[3];
            }

            if (isset($day, $month, $year) && checkdate($month, $day, $year)) {
                $date = Carbon::create($year, $month, $day, 0, 0, 0);
            }

            unset($day, $month, $year);

            if (! $date) {
                $e[] = "bad date \"$dateText\" (use day/month/year)";
            }


            // initiated by
            $initiatedBy = $get('initiated_by');
            if ($initiatedBy === '') {
                $e[] = 'empty "INITIATED BY"';
            }

            // department: code or name
            $deptText = mb_strtolower($get('department'));
            $deptId = $departments[$deptText] ?? $deptByName[$deptText] ?? null;
            if (! $deptId) {
                $e[] = "unknown department \"{$get('department')}\"";
            }

            // item type
            $itemId = $items[mb_strtolower($get('item'))] ?? null;
            if (! $itemId) {
                $e[] = "unknown item \"{$get('item')}\"";
            }

            // issue types: "Hardware & Software" => both
            $issueIds = [];
            $issueText = $get('issue_type');
            if ($issueText !== '') {
                foreach (preg_split('/\s*(?:&|,|\/|\band\b)\s*/iu', $issueText) as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }
                    $id = $issueTypes[mb_strtolower($part)] ?? null;
                    if ($id) {
                        $issueIds[] = $id;
                    } else {
                        $e[] = "unknown issue type \"$part\"";
                    }
                }
            }
            sort($issueIds);

            // status
            $statusKey = str_replace([' ', '-'], '_', mb_strtolower($get('status')));
            $status = self::STATUS_MAP[$statusKey] ?? null;
            if (! $status) {
                $e[] = "unknown status \"{$get('status')}\"";
            }

            // worker
            $workerText = $get('worker');
            $workerId = null;
            if ($workerText !== '') {
                $workerId = $users[mb_strtolower($workerText)] ?? null;
                if (! $workerId) {
                    $e[] = "unknown worker \"$workerText\" (must match a user name exactly)";
                }
            }

            if ($e) {
                $errors[] = "Line $line: " . implode('; ', $e);
                continue;
            }

            $details = $get('details') === '' ? null : $get('details');

            $rows[] = [
                'line'          => $line,
                'date'          => $date,
                'initiated_by'  => $initiatedBy,
                'department_id' => $deptId,
                'item_type_id'  => $itemId,
                'issue_type_ids' => $issueIds,
                'issue_details' => $details,
                'status'        => $status,
                'assigned_to'   => $workerId,
                // the person who did the work logged it; otherwise the fallback user
                'created_by'    => $workerId ?? $fallback->id,
                'key'           => implode('|', [
                    $date->toDateString(), mb_strtolower($initiatedBy), $deptId, $itemId,
                    implode(',', $issueIds), mb_strtolower((string) $details), $status, $workerId,
                ]),
            ];
        }
        fclose($handle);

        if ($errors) {
            $this->error(count($errors) . ' row(s) have problems. Nothing was imported:');
            foreach ($errors as $message) {
                $this->line("  $message");
            }
            return self::FAILURE;
        }

        // ---- skip rows that already exist (so running it twice is safe) ----
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['key']][] = $r;
        }

        $toImport = [];
        $skipped = 0;
        foreach ($groups as $items_) {
            $inFile = $this->option('collapse-duplicates') ? 1 : count($items_);
            $need = max(0, $inFile - $this->existingCount($items_[0]));
            $skipped += count($items_) - $need;
            foreach (array_slice($items_, 0, $need) as $r) {
                $toImport[] = $r;
            }
        }
        usort($toImport, fn ($a, $b) => $a['line'] <=> $b['line']);

        $this->info('Rows read from file:     ' . count($rows));
        $this->info('Already in database:     ' . $skipped . ' (skipped)');
        $this->info('Will be imported:        ' . count($toImport));

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing was saved.');
            return self::SUCCESS;
        }

        if (! $toImport) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($toImport) {
            foreach ($toImport as $r) {
                $stamp = $r['date']->copy()->setTime(9, 0);

                $log = new SupportLog();
                $log->forceFill([
                    'ticket_number' => (string) Str::uuid(), // replaced below
                    'issue_date'    => $r['date']->toDateString(),
                    'initiated_by'  => $r['initiated_by'],
                    'department_id' => $r['department_id'],
                    'item_type_id'  => $r['item_type_id'],
                    'status'        => $r['status'],
                    'issue_details' => $r['issue_details'],
                    'assigned_to'   => $r['assigned_to'],
                    'created_by'    => $r['created_by'],
                    'created_at'    => $stamp,
                    'updated_at'    => $stamp,
                ])->save();

                DB::table('support_logs')->where('id', $log->id)->update([
                    'ticket_number' => sprintf('SL-%s-%06d', $r['date']->format('Y'), $log->id),
                ]);

                if ($r['issue_type_ids']) {
                    $log->issueTypes()->sync($r['issue_type_ids']);
                }
            }
        });

        $this->info('Done. Imported ' . count($toImport) . ' support logs.');
        return self::SUCCESS;
    }

    private function clean(?string $value): string
    {
        $value = preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $value) ?? '';
        return trim($value);
    }

    // lowercase name => id
    private function lookup(string $table, string $column): array
    {
        $map = [];
        foreach (DB::table($table)->get(['id', $column]) as $row) {
            $map[mb_strtolower(trim((string) $row->$column))] = $row->id;
        }
        return $map;
    }

    private function existingCount(array $r): int
    {
        $q = SupportLog::query()
            ->whereDate('issue_date', $r['date']->toDateString())
            ->where('initiated_by', $r['initiated_by'])
            ->where('department_id', $r['department_id'])
            ->where('item_type_id', $r['item_type_id'])
            ->where('status', $r['status']);

        $r['issue_details'] === null
            ? $q->whereNull('issue_details')
            : $q->where('issue_details', $r['issue_details']);

        $r['assigned_to'] === null
            ? $q->whereNull('assigned_to')
            : $q->where('assigned_to', $r['assigned_to']);

        return $q->count();
    }
}