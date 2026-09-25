<?php

namespace App\Http\Controllers;

use App\Enums\SupportLogPriority;
use App\Enums\SupportLogStatus;
use App\Http\Requests\SupportLogAnalyticsRequest;
use App\Models\SupportLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function summary(SupportLogAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $baseQuery = SupportLogQuery::apply(SupportLog::query(), $filters);
        $total = (clone $baseQuery)->count();
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $metrics = [
            'total' => $total,
            'total_logs' => $total,
            'open' => $this->countStatus($baseQuery, SupportLogStatus::Open),
            'in_progress' => $this->countStatus($baseQuery, SupportLogStatus::InProgress),
            'indoor_repair' => $this->countStatus($baseQuery, SupportLogStatus::IndoorRepair),
            'outdoor_repair' => $this->countStatus($baseQuery, SupportLogStatus::OutdoorRepair),
            'resolved' => $this->countStatus($baseQuery, SupportLogStatus::Resolved),
            'closed' => $this->countStatus($baseQuery, SupportLogStatus::Closed),
            'cancelled' => $this->countStatus($baseQuery, SupportLogStatus::Cancelled),
            'unresolved' => (clone $baseQuery)
                ->whereNotIn('status', [
                    SupportLogStatus::Resolved->value,
                    SupportLogStatus::Closed->value,
                    SupportLogStatus::Cancelled->value,
                ])
                ->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_to')->count(),
            'critical' => (clone $baseQuery)->where('priority', SupportLogPriority::Critical->value)->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', $today)->count(),
            'week' => (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'month' => (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count(),
            'created_today' => (clone $baseQuery)->whereDate('created_at', $today)->count(),
            'created_this_week' => (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'created_this_month' => (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count(),
            'overdue' => (clone $baseQuery)
                ->whereNotIn('status', [
                    SupportLogStatus::Resolved->value,
                    SupportLogStatus::Closed->value,
                    SupportLogStatus::Cancelled->value,
                ])
                ->where('created_at', '<', now()->subDays(7))
                ->count(),
            'average_resolution_time_hours' => $this->averageResolutionHours($baseQuery),
        ];

        return response()->json([
            'filters' => $filters,
            'period' => [
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
            'metrics' => $metrics,
            'status_breakdown' => $this->statusBreakdown($baseQuery),
            'priority_breakdown' => $this->priorityBreakdown($baseQuery),
            'department_breakdown' => $this->departmentBreakdown($baseQuery),
            'resource_breakdown' => $this->resourceBreakdown($baseQuery),
            'issue_type_breakdown' => $this->issueTypeBreakdown($baseQuery),
            'item_breakdown' => $this->itemBreakdown($baseQuery),
            'overdue_rule' => 'Active logs older than 7 days from created_at are overdue.',
            'resolution_time_basis' => 'created_at to resolved_at; only rows with both timestamps are included.',
            'generated_at' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    private function countStatus(Builder $query, SupportLogStatus $status): int
    {
        return (clone $query)->where('status', $status->value)->count();
    }

    private function statusBreakdown(Builder $query): array
    {
        $counts = (clone $query)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return array_map(
            static fn (SupportLogStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ],
            SupportLogStatus::cases(),
        );
    }

    private function priorityBreakdown(Builder $query): array
    {
        $counts = (clone $query)
            ->select('priority')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');

        return array_map(
            static fn (SupportLogPriority $priority): array => [
                'priority' => $priority->value,
                'label' => $priority->label(),
                'count' => (int) ($counts[$priority->value] ?? 0),
            ],
            SupportLogPriority::cases(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function departmentBreakdown(Builder $query): array
    {
        return (clone $query)
            ->leftJoin('departments', 'departments.id', '=', 'support_logs.department_id')
            ->select('support_logs.department_id')
            ->selectRaw('departments.name as name')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('support_logs.department_id', 'departments.name')
            ->orderByDesc('aggregate')
            ->get()
            ->map(static fn (object $row): array => [
                'department_id' => $row->department_id === null ? null : (int) $row->department_id,
                'department' => $row->name ?? 'Unassigned department',
                'count' => (int) $row->aggregate,
                'total' => (int) $row->aggregate,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resourceBreakdown(Builder $query): array
    {
        return (clone $query)
            ->leftJoin('users as assigned_resources', 'assigned_resources.id', '=', 'support_logs.assigned_to')
            ->select('support_logs.assigned_to')
            ->selectRaw('assigned_resources.name as name')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('support_logs.assigned_to', 'assigned_resources.name')
            ->orderByDesc('aggregate')
            ->get()
            ->map(static fn (object $row): array => [
                'assigned_to' => $row->assigned_to === null ? null : (int) $row->assigned_to,
                'resource' => $row->name ?? 'Unassigned',
                'count' => (int) $row->aggregate,
                'total' => (int) $row->aggregate,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function issueTypeBreakdown(Builder $query): array
    {
        return (clone $query)
            ->join('support_log_issue_types as support_log_issue_types', 'support_log_issue_types.support_log_id', '=', 'support_logs.id')
            ->join('issue_types as issue_types', 'issue_types.id', '=', 'support_log_issue_types.issue_type_id')
            ->select('issue_types.id as issue_type_id')
            ->selectRaw('issue_types.name as name')
            ->selectRaw('COUNT(DISTINCT support_logs.id) as aggregate')
            ->groupBy('issue_types.id', 'issue_types.name')
            ->orderByDesc('aggregate')
            ->get()
            ->map(static fn (object $row): array => [
                'issue_type_id' => (int) $row->issue_type_id,
                'issue_type' => $row->name,
                'name' => $row->name,
                'count' => (int) $row->aggregate,
                'total' => (int) $row->aggregate,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function itemBreakdown(Builder $query): array
    {
        return (clone $query)
            ->join('item_types as item_types', 'item_types.id', '=', 'support_logs.item_type_id')
            ->select('item_types.id as item_type_id')
            ->selectRaw('item_types.name as name')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('item_types.id', 'item_types.name')
            ->orderByDesc('aggregate')
            ->get()
            ->map(static fn (object $row): array => [
                'item_type_id' => (int) $row->item_type_id,
                'item_type' => $row->name,
                'name' => $row->name,
                'count' => (int) $row->aggregate,
                'total' => (int) $row->aggregate,
            ])
            ->values()
            ->all();
    }

    private function averageResolutionHours(Builder $query): ?float
    {
        $count = 0;
        $totalHours = 0.0;

        (clone $query)
            ->whereIn('status', [
                SupportLogStatus::Resolved->value,
                SupportLogStatus::Closed->value,
            ])
            ->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->select(['created_at', 'resolved_at'])
            ->cursor()
            ->each(function (SupportLog $supportLog) use (&$count, &$totalHours): void {
                $createdAt = Carbon::parse($supportLog->getRawOriginal('created_at'));
                $resolvedAt = Carbon::parse($supportLog->getRawOriginal('resolved_at'));
                $totalHours += max(0, $createdAt->diffInMinutes($resolvedAt) / 60);
                $count++;
            });

        return $count === 0 ? null : round($totalHours / $count, 2);
    }
}
