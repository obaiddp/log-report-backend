<?php

namespace App\Http\Controllers;

use App\Enums\SupportLogStatus;
use App\Http\Requests\SupportLogAnalyticsRequest;
use App\Models\SupportLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportLogReportController extends Controller
{
    public function byDepartment(SupportLogAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $baseQuery = SupportLogQuery::apply(SupportLog::query(), $filters);
        $issueTypesByDepartment = $this->issueTypeCountsByDepartment($baseQuery);
        $rows = SupportLogQuery::apply(
            SupportLog::query()->leftJoin('departments', 'departments.id', '=', 'support_logs.department_id'),
            $filters,
        )
            ->select('support_logs.department_id')
            ->selectRaw('departments.name as department_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as open_count', [SupportLogStatus::Open->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as in_progress_count', [SupportLogStatus::InProgress->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as resolved_count', [SupportLogStatus::Resolved->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as closed_count', [SupportLogStatus::Closed->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as cancelled_count', [SupportLogStatus::Cancelled->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as indoor_repair_count', [SupportLogStatus::IndoorRepair->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as outdoor_repair_count', [SupportLogStatus::OutdoorRepair->value])
            ->groupBy('support_logs.department_id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(function (object $row) use ($issueTypesByDepartment): array {
                $departmentRow = $this->departmentRow($row);
                $departmentRow['issue_types'] = $issueTypesByDepartment[(string) $row->department_id] ?? [];

                return $departmentRow;
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'filters' => $filters,
            'meta' => ['total' => count($rows), 'generated_at' => now()->toIso8601String()],
        ]);
    }

    public function byResource(SupportLogAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $baseQuery = SupportLogQuery::apply(SupportLog::query(), $filters);
        $issueTypesByResource = $this->issueTypeCountsByResource($baseQuery);
        $rows = SupportLogQuery::apply(
            SupportLog::query()->leftJoin('users as assigned_resources', 'assigned_resources.id', '=', 'support_logs.assigned_to'),
            $filters,
        )
            ->select('support_logs.assigned_to')
            ->selectRaw('assigned_resources.name as resource_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as open_count', [SupportLogStatus::Open->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as in_progress_count', [SupportLogStatus::InProgress->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as resolved_count', [SupportLogStatus::Resolved->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as closed_count', [SupportLogStatus::Closed->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as cancelled_count', [SupportLogStatus::Cancelled->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as indoor_repair_count', [SupportLogStatus::IndoorRepair->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as outdoor_repair_count', [SupportLogStatus::OutdoorRepair->value])
            ->groupBy('support_logs.assigned_to', 'assigned_resources.name')
            ->orderByDesc('total')
            ->get()
            ->map(function (object $row) use ($issueTypesByResource): array {
                $resourceRow = $this->resourceRow($row);
                $resourceRow['issue_types'] = $issueTypesByResource[(string) $row->assigned_to] ?? [];

                return $resourceRow;
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'filters' => $filters,
            'meta' => ['total' => count($rows), 'generated_at' => now()->toIso8601String()],
        ]);
    }

    public function byIssueType(SupportLogAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $rows = SupportLogQuery::apply(
            SupportLog::query()
                ->join('support_log_issue_types as support_log_issue_types', 'support_log_issue_types.support_log_id', '=', 'support_logs.id')
                ->join('issue_types as issue_types', 'issue_types.id', '=', 'support_log_issue_types.issue_type_id'),
            $filters,
        )
            ->select('support_log_issue_types.issue_type_id')
            ->selectRaw('issue_types.name as issue_type_name')
            ->selectRaw('COUNT(DISTINCT support_logs.id) as total')
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as open_count', [SupportLogStatus::Open->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as in_progress_count', [SupportLogStatus::InProgress->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as resolved_count', [SupportLogStatus::Resolved->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as closed_count', [SupportLogStatus::Closed->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as cancelled_count', [SupportLogStatus::Cancelled->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as indoor_repair_count', [SupportLogStatus::IndoorRepair->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as outdoor_repair_count', [SupportLogStatus::OutdoorRepair->value])
            ->groupBy('support_log_issue_types.issue_type_id', 'issue_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => $this->issueTypeRow($row))
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'filters' => $filters,
            'meta' => ['total' => count($rows), 'generated_at' => now()->toIso8601String()],
        ]);
    }

    public function byItem(SupportLogAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $rows = SupportLogQuery::apply(
            SupportLog::query()
                ->join('item_types as item_types', 'item_types.id', '=', 'support_logs.item_type_id'),
            $filters,
        )
            ->select('item_types.id as item_type_id')
            ->selectRaw('item_types.name as item_type_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as open_count', [SupportLogStatus::Open->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as in_progress_count', [SupportLogStatus::InProgress->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as resolved_count', [SupportLogStatus::Resolved->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as closed_count', [SupportLogStatus::Closed->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as cancelled_count', [SupportLogStatus::Cancelled->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as indoor_repair_count', [SupportLogStatus::IndoorRepair->value])
            ->selectRaw('SUM(CASE WHEN support_logs.status = ? THEN 1 ELSE 0 END) as outdoor_repair_count', [SupportLogStatus::OutdoorRepair->value])
            ->groupBy('item_types.id', 'item_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => $this->itemTypeRow($row))
            ->values()
            ->all();

        return response()->json([
            'data' => $rows,
            'filters' => $filters,
            'meta' => ['total' => count($rows), 'generated_at' => now()->toIso8601String()],
        ]);
    }

    public function byStatus(SupportLogAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $baseQuery = SupportLogQuery::apply(SupportLog::query(), $filters);
        $total = (clone $baseQuery)->count();
        $counts = (clone $baseQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $data = array_map(
            fn (SupportLogStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
                'percentage' => $total === 0 ? 0 : round((int) ($counts[$status->value] ?? 0) / $total * 100, 2),
            ],
            SupportLogStatus::cases(),
        );

        return response()->json([
            'data' => $data,
            'filters' => $filters,
            'meta' => ['total' => $total, 'generated_at' => now()->toIso8601String()],
        ]);
    }

    public function export(SupportLogAnalyticsRequest $request): StreamedResponse
    {
        $filters = $request->filters();
        $query = SupportLogQuery::apply(SupportLog::query(), $filters)
            ->with(SupportLogQuery::relations())
            ->orderBy('id');
        $dateFrom = $filters['date_from'] ?? now()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();
        $filename = 'support-logs-'.str_replace('-', '', (string) $dateFrom).'-'.str_replace('-', '', (string) $dateTo).'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fputcsv($handle, [
                'id',
                'ticket_number',
                'issue_date',
                'initiated_by',
                'department',
                'item_type',
                'issue_types',
                'description',
                'status',
                'priority',
                'assigned_to',
                'assigned_resource',
                'created_by',
                'creator',
                'resolution_notes',
                'internal_remarks',
                'resolved_at',
                'closed_at',
                'created_at',
                'updated_at',
            ], ',', '"', '');

            $query->lazyById(500)->each(function (SupportLog $supportLog) use ($handle): void {
                $department = $supportLog->department;
                $itemType = $supportLog->itemType;
                $assignedResource = $supportLog->assignedTo;
                $creator = $supportLog->creator;
                $issueTypes = $supportLog->issueTypes->pluck('name')->implode(', ');

                fputcsv($handle, [
                    $supportLog->id,
                    $this->csvCell($supportLog->ticket_number),
                    $supportLog->issue_date->toDateString(),
                    $this->csvCell($supportLog->initiated_by),
                    $this->csvCell($department?->name),
                    $this->csvCell($itemType?->name),
                    $this->csvCell($issueTypes),
                    $this->csvCell($supportLog->description),
                    $supportLog->status->value,
                    $supportLog->priority->value,
                    $supportLog->assigned_to,
                    $this->csvCell($assignedResource?->name),
                    $supportLog->created_by,
                    $this->csvCell($creator?->name),
                    $this->csvCell($supportLog->resolution_notes),
                    $this->csvCell($supportLog->internal_remarks),
                    $supportLog->resolved_at?->toIso8601String(),
                    $supportLog->closed_at?->toIso8601String(),
                    $supportLog->created_at?->toIso8601String(),
                    $supportLog->updated_at?->toIso8601String(),
                ], ',', '"', '');
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function issueTypeCountsByDepartment(Builder $query): array
    {
        $rows = (clone $query)
            ->join('support_log_issue_types as support_log_issue_types', 'support_log_issue_types.support_log_id', '=', 'support_logs.id')
            ->join('issue_types as issue_types', 'issue_types.id', '=', 'support_log_issue_types.issue_type_id')
            ->select('support_logs.department_id')
            ->select('issue_types.id as issue_type_id')
            ->selectRaw('issue_types.name as issue_type_name')
            ->selectRaw('COUNT(DISTINCT support_logs.id) as aggregate')
            ->groupBy('support_logs.department_id', 'issue_types.id', 'issue_types.name')
            ->orderByDesc('aggregate')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $key = (string) $row->department_id;
            $counts[$key][] = [
                'issue_type_id' => (int) $row->issue_type_id,
                'issue_type' => $row->issue_type_name,
                'name' => $row->issue_type_name,
                'count' => (int) $row->aggregate,
            ];
        }

        return $counts;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function issueTypeCountsByResource(Builder $query): array
    {
        $rows = (clone $query)
            ->join('support_log_issue_types as support_log_issue_types', 'support_log_issue_types.support_log_id', '=', 'support_logs.id')
            ->join('issue_types as issue_types', 'issue_types.id', '=', 'support_log_issue_types.issue_type_id')
            ->select('support_logs.assigned_to')
            ->select('issue_types.id as issue_type_id')
            ->selectRaw('issue_types.name as issue_type_name')
            ->selectRaw('COUNT(DISTINCT support_logs.id) as aggregate')
            ->groupBy('support_logs.assigned_to', 'issue_types.id', 'issue_types.name')
            ->orderByDesc('aggregate')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $key = (string) $row->assigned_to;
            $counts[$key][] = [
                'issue_type_id' => (int) $row->issue_type_id,
                'issue_type' => $row->issue_type_name,
                'name' => $row->issue_type_name,
                'count' => (int) $row->aggregate,
            ];
        }

        return $counts;
    }

    private function departmentRow(object $row): array
    {
        $total = (int) $row->total;

        return [
            'department_id' => $row->department_id === null ? null : (int) $row->department_id,
            'department' => $row->department_name,
            'total' => $total,
            'count' => $total,
            'open' => (int) $row->open_count,
            'in_progress' => (int) $row->in_progress_count,
            'resolved' => (int) $row->resolved_count,
            'closed' => (int) $row->closed_count,
            'cancelled' => (int) $row->cancelled_count,
            'indoor_repair' => (int) $row->indoor_repair_count,
            'outdoor_repair' => (int) $row->outdoor_repair_count,
        ];
    }

    private function resourceRow(object $row): array
    {
        $total = (int) $row->total;

        return [
            'assigned_to' => $row->assigned_to === null ? null : (int) $row->assigned_to,
            'resource' => $row->resource_name,
            'total' => $total,
            'count' => $total,
            'open' => (int) $row->open_count,
            'in_progress' => (int) $row->in_progress_count,
            'resolved' => (int) $row->resolved_count,
            'closed' => (int) $row->closed_count,
            'cancelled' => (int) $row->cancelled_count,
            'indoor_repair' => (int) $row->indoor_repair_count,
            'outdoor_repair' => (int) $row->outdoor_repair_count,
        ];
    }

    private function issueTypeRow(object $row): array
    {
        $total = (int) $row->total;

        return [
            'issue_type_id' => (int) $row->issue_type_id,
            'issue_type' => $row->issue_type_name,
            'name' => $row->issue_type_name,
            'total' => $total,
            'count' => $total,
            'open' => (int) $row->open_count,
            'in_progress' => (int) $row->in_progress_count,
            'resolved' => (int) $row->resolved_count,
            'closed' => (int) $row->closed_count,
            'cancelled' => (int) $row->cancelled_count,
            'indoor_repair' => (int) $row->indoor_repair_count,
            'outdoor_repair' => (int) $row->outdoor_repair_count,
        ];
    }

    private function itemTypeRow(object $row): array
    {
        $total = (int) $row->total;

        return [
            'item_type_id' => (int) $row->item_type_id,
            'item_type' => $row->item_type_name,
            'name' => $row->item_type_name,
            'total' => $total,
            'count' => $total,
            'open' => (int) $row->open_count,
            'in_progress' => (int) $row->in_progress_count,
            'resolved' => (int) $row->resolved_count,
            'closed' => (int) $row->closed_count,
            'cancelled' => (int) $row->cancelled_count,
            'indoor_repair' => (int) $row->indoor_repair_count,
            'outdoor_repair' => (int) $row->outdoor_repair_count,
        ];
    }

    private function csvCell(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'{$value}" : $value;
    }
}
