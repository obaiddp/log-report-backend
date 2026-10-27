<?php

namespace App\Http\Controllers;

use App\Models\AssetInspection;
use App\Models\SupportLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController
{
    // ------------------------------------------------------------------
    // Scopes
    //   mine       -> only records I am involved in
    //   department -> records involving anyone in MY department
    //   all        -> everything
    // 'department' needs permission dashboard_department_view
    // 'all'        needs permission dashboard_all_view
    // ------------------------------------------------------------------

    public function userPerformance(): JsonResponse
    {
        $logsByUser = SupportLog::whereNotNull('assigned_to')
            ->get(['id', 'assigned_to', 'status', 'created_at', 'resolved_at'])
            ->groupBy('assigned_to');

        $data = User::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($logsByUser) {
                $mine = $logsByUser->get($user->id, collect());
                $resolved = $mine->where('status', 'solved');

                $avgHours = $resolved
                    ->filter(fn ($l) => $l->resolved_at)
                    ->avg(fn ($l) => $l->created_at
                        ->diffInMinutes(Carbon::parse($l->resolved_at), true) / 60);

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'tickets_assigned' => $mine->count(),
                    'tickets_resolved' => $resolved->count(),
                    'avg_resolution_hours' => $avgHours !== null ? round($avgHours, 2) : null,
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    // GET /api/dashboard/filter-options?scope=...
    // People for the filter dropdowns (the Users endpoint is super admin only).
    public function filterOptions(Request $request): JsonResponse
    {
        $scope = $this->resolveScope($request);
        $ids = $this->scopeUserIds($request, $scope);

        $users = User::query()
            ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('name')
            ->get(['id', 'name']);

        $approvers = User::whereHas('department', fn ($q) => $q->where('code', 'admin-log'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'scope' => $scope,
                'users' => $users,
                'approvers' => $approvers,
            ],
        ]);
    }

    // GET /api/dashboard/support-logs
    // Filters: scope, from, to, department_id, item_type_id, status, created_by, assigned_to
    public function supportLogs(Request $request): JsonResponse
    {
        $scope = $this->resolveScope($request);
        $f = $this->validatedFilters($request);
        $q = $this->supportBase($request, $scope, $f);

        $byStatus = $this->groupCount($q, 'support_logs.status');
        $total = array_sum(array_column($byStatus, 'total'));
        $solved = collect($byStatus)->firstWhere('label', 'solved')['total'] ?? 0;

        $avgHours = (clone $q)
            ->where('support_logs.status', 'solved')
            ->whereNotNull('support_logs.resolved_at')
            ->selectRaw('avg(extract(epoch from (support_logs.resolved_at - support_logs.created_at)) / 3600) as hrs')
            ->value('hrs');

        $trendFrom = $f['from'] ?? now()->subDays(29)->toDateString();
        $trend = (clone $q)
            ->whereDate('support_logs.issue_date', '>=', $trendFrom)
            ->selectRaw('CAST(support_logs.issue_date AS date) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => (string) $r->day, 'total' => (int) $r->total])
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'scope' => $scope,
                'summary' => [
                    'total' => $total,
                    'solved' => $solved,
                    'open' => $total - $solved,
                    'last_7_days' => (clone $q)->where('support_logs.created_at', '>=', now()->subDays(7))->count(),
                    'avg_resolution_hours' => $avgHours !== null ? round((float) $avgHours, 2) : null,
                ],
                'by_status' => $byStatus,
                'by_department' => $this->groupCountWithName($q, 'support_logs.department_id', 'departments'),
                'by_item_type' => $this->groupCountWithName($q, 'support_logs.item_type_id', 'item_types'),
                'by_issue_type' => $this->supportByIssueType($q),
                'by_creator' => $this->groupCountWithName($q, 'support_logs.created_by', 'users'),
                'by_assignee' => $this->groupCountWithName($q, 'support_logs.assigned_to', 'users'),
                'trend' => $trend,
            ],
        ]);
    }

    // GET /api/dashboard/asset-inspections
    // Filters: scope, from, to, department_id, item_type_id, status (item status),
    //          approval_status, prepared_by, admin_user
    public function assetInspections(Request $request): JsonResponse
    {
        $scope = $this->resolveScope($request);
        $f = $this->validatedFilters($request);
        $me = $request->user()->id;
        $q = $this->assetBase($request, $scope, $f);

        $byApproval = $this->groupCount($q, 'asset_inspections.approval_status');
        $count = fn (string $s) => collect($byApproval)->firstWhere('label', $s)['total'] ?? 0;

        $avgHours = (clone $q)
            ->where('asset_inspections.approval_status', AssetInspection::APPROVAL_COMPLETED)
            ->whereNotNull('asset_inspections.admin_signed_at')
            ->selectRaw('avg(extract(epoch from (asset_inspections.admin_signed_at - asset_inspections.created_at)) / 3600) as hrs')
            ->value('hrs');

        $trendFrom = $f['from'] ?? now()->subDays(29)->toDateString();
        $trend = (clone $q)
            ->whereDate('asset_inspections.created_at', '>=', $trendFrom)
            ->selectRaw('CAST(asset_inspections.created_at AS date) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => (string) $r->day, 'total' => (int) $r->total])
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'scope' => $scope,
                'summary' => [
                    'total' => array_sum(array_column($byApproval, 'total')),
                    'pending' => $count(AssetInspection::APPROVAL_PENDING_ADMIN),
                    'returned' => $count(AssetInspection::APPROVAL_RETURNED),
                    'completed' => $count(AssetInspection::APPROVAL_COMPLETED),
                    'avg_approval_hours' => $avgHours !== null ? round((float) $avgHours, 2) : null,
                    // personal numbers, not affected by scope or filters
                    'needs_my_signature' => AssetInspection::where('admin_user', $me)
                        ->where('approval_status', AssetInspection::APPROVAL_PENDING_ADMIN)->count(),
                    'returned_to_me' => AssetInspection::where('prepared_by', $me)
                        ->where('approval_status', AssetInspection::APPROVAL_RETURNED)->count(),
                ],
                'by_approval_status' => $byApproval,
                'by_item_status' => $this->groupCount($q, 'asset_inspections.status'),
                'by_department' => $this->groupCountWithName($q, 'asset_inspections.department_id', 'departments'),
                'by_item_type' => $this->groupCountWithName($q, 'asset_inspections.item_type_id', 'item_types'),
                'by_preparer' => $this->assetByPerson($q, 'asset_inspections.prepared_by'),
                'by_approver' => $this->assetByPerson($q, 'asset_inspections.admin_user'),
                'trend' => $trend,
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // Base queries (scope + filters)
    // ------------------------------------------------------------------

    private function supportBase(Request $request, string $scope, array $f): Builder
    {
        $q = SupportLog::query();

        $ids = $this->scopeUserIds($request, $scope);
        if ($ids !== null) {
            $q->where(fn ($w) => $w->whereIn('support_logs.created_by', $ids)
                ->orWhereIn('support_logs.assigned_to', $ids));
        }

        if (! empty($f['from'])) {
            $q->whereDate('support_logs.issue_date', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $q->whereDate('support_logs.issue_date', '<=', $f['to']);
        }
        foreach (['department_id', 'item_type_id', 'status', 'created_by', 'assigned_to'] as $col) {
            if (! empty($f[$col])) {
                $q->where("support_logs.$col", $f[$col]);
            }
        }

        return $q;
    }

    private function assetBase(Request $request, string $scope, array $f): Builder
    {
        $q = AssetInspection::query();

        $ids = $this->scopeUserIds($request, $scope);
        if ($ids !== null) {
            $q->where(fn ($w) => $w->whereIn('asset_inspections.prepared_by', $ids)
                ->orWhereIn('asset_inspections.admin_user', $ids));
        }

        if (! empty($f['from'])) {
            $q->whereDate('asset_inspections.created_at', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $q->whereDate('asset_inspections.created_at', '<=', $f['to']);
        }
        foreach (['department_id', 'item_type_id', 'status', 'approval_status', 'prepared_by', 'admin_user'] as $col) {
            if (! empty($f[$col])) {
                $q->where("asset_inspections.$col", $f[$col]);
            }
        }

        return $q;
    }

    // ------------------------------------------------------------------
    // Grouping helpers. (clone $q) copies the builder so every breakdown
    // starts from the same filtered base.
    // ------------------------------------------------------------------

    // [{label, total}]  grouped by a plain column
    private function groupCount(Builder $q, string $column): array
    {
        return (clone $q)
            ->selectRaw("$column as label, count(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'total' => (int) $r->total])
            ->values()
            ->all();
    }

    // [{id, name, total}]  grouped by a foreign key, with the name joined in
    private function groupCountWithName(Builder $q, string $fk, string $table): array
    {
        return (clone $q)
            ->leftJoin($table, "$table.id", '=', $fk)
            ->selectRaw("$table.id as id, COALESCE($table.name, 'Unassigned') as name, count(*) as total")
            ->groupBy("$table.id", "$table.name")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'total' => (int) $r->total])
            ->values()
            ->all();
    }

    // Per person, with a pending / returned / completed split
    private function assetByPerson(Builder $q, string $fk): array
    {
        return (clone $q)
            ->join('users', 'users.id', '=', $fk)
            ->selectRaw(
                "users.id as id, users.name as name, count(*) as total,
                 sum(case when asset_inspections.approval_status = 'pending_admin' then 1 else 0 end) as pending,
                 sum(case when asset_inspections.approval_status = 'returned' then 1 else 0 end) as returned,
                 sum(case when asset_inspections.approval_status = 'completed' then 1 else 0 end) as completed"
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'total' => (int) $r->total,
                'pending' => (int) $r->pending,
                'returned' => (int) $r->returned,
                'completed' => (int) $r->completed,
            ])
            ->values()
            ->all();
    }

    // Uses the pivot table support_log_issue_types. If its columns are named
    // differently in your migration, this returns [] instead of crashing.
    private function supportByIssueType(Builder $q): array
    {
        if (
            ! Schema::hasTable('support_log_issue_types')
            || ! Schema::hasColumn('support_log_issue_types', 'support_log_id')
            || ! Schema::hasColumn('support_log_issue_types', 'issue_type_id')
        ) {
            return [];
        }

        return DB::table('support_log_issue_types as p')
            ->join('issue_types as it', 'it.id', '=', 'p.issue_type_id')
            ->whereIn('p.support_log_id', (clone $q)->select('support_logs.id'))
            ->selectRaw('it.id as id, it.name as name, count(*) as total')
            ->groupBy('it.id', 'it.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'total' => (int) $r->total])
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // Scope + permission helpers
    // ------------------------------------------------------------------

    private function resolveScope(Request $request): string
    {
        $scope = (string) $request->query('scope', 'mine');

        abort_unless(in_array($scope, ['mine', 'department', 'all'], true), 422, 'Invalid scope.');

        $needed = [
            'department' => 'dashboard_department_view',
            'all' => 'dashboard_all_view',
        ][$scope] ?? null;

        abort_if(
            $needed !== null && ! $this->can($request, $needed),
            403,
            'You cannot view this dashboard scope.'
        );

        return $scope;
    }

    // null means "no restriction" (everyone)
    private function scopeUserIds(Request $request, string $scope): ?array
    {
        $user = $request->user();

        return match ($scope) {
            'mine' => [$user->id],
            'department' => User::where('department_id', $user->department_id)->pluck('id')->all(),
            default => null,
        };
    }

    private function can(Request $request, string $permission): bool
    {
        return (bool) $request->user()->role?->permissions()
            ->where('permissions.name', $permission)
            ->exists();
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'scope' => ['sometimes', 'in:mine,department,all'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'item_type_id' => ['nullable', 'integer', 'exists:item_types,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'approval_status' => ['nullable', 'in:pending_admin,completed,returned'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'admin_user' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }
}