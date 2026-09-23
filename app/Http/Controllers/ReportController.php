<?php

namespace App\Http\Controllers;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Http\Requests\ReportRequest;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function summary(ReportRequest $request): JsonResponse
    {
        $period = $request->period();
        $assetQuery = $this->assetQuery($request);
        $inspectionQuery = $this->inspectionQuery($request, $period);

        $statusBreakdown = $this->enumCounts($inspectionQuery, 'status', InspectionStatus::cases(), 'status');
        $purchaseVsRepair = $this->enumCounts($inspectionQuery, 'category', InspectionCategory::cases(), 'category');
        $assetDistribution = $this->enumCounts($assetQuery, 'type', AssetType::cases(), 'type');

        $inspectionCount = array_sum(array_column($statusBreakdown, 'count'));
        $repairCount = $this->countForCase($purchaseVsRepair, InspectionCategory::Repair->value);
        $newPurchaseCount = $this->countForCase($purchaseVsRepair, InspectionCategory::NewPurchase->value);
        $pendingCount = $inspectionCount - $this->countForCase($statusBreakdown, InspectionStatus::Sold->value);

        return response()->json([
            'period' => [
                'range' => $period['requested_range'],
                'from' => $period['from']->toDateString(),
                'to' => $period['to']->toDateString(),
                'granularity' => $period['granularity'],
                'timezone' => config('app.timezone'),
                'generated_at' => CarbonImmutable::now()->toIso8601String(),
            ],
            'metrics' => [
                'total_assets' => (clone $assetQuery)->count(),
                'total_users' => $this->userQuery($request)->count(),
                'total_inspections' => $inspectionCount,
                'total_new_purchases' => $newPurchaseCount,
                'total_repairs' => $repairCount,
                'pending_inspections' => $pendingCount,
            ],
            'status_breakdown' => $statusBreakdown,
            'asset_distribution' => $assetDistribution,
            'ram_usage_by_department' => $this->ramUsageByDepartment($request),
            'purchase_vs_repair' => $purchaseVsRepair,
            'inspection_trend' => $this->inspectionTrend($inspectionQuery, $period),
            'technician_workload' => $this->technicianWorkload($request, $inspectionQuery),
        ]);
    }

    public function export(ReportRequest $request): StreamedResponse
    {
        $period = $request->period();
        $query = $this->inspectionQuery($request, $period)->orderBy('id');
        $filename = "asset-inspections-{$period['from']->format('Ymd')}-{$period['to']->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fputcsv($handle, [
                'id',
                'problem_id',
                'inspection_date',
                'status',
                'category',
                'sub_category',
                'remarks',
                'asset_id',
                'asset_tag',
                'asset_type',
                'brand',
                'model',
                'serial_number',
                'user_id',
                'user_name',
                'user_email',
                'department_id',
                'department_name',
                'technical_personnel_id',
                'technical_personnel_name',
                'created_by',
                'created_by_name',
                'created_at',
            ], ',', '"', '');

            $query
                ->with(['asset.user.department', 'technicalPersonnel', 'createdBy'])
                ->lazyById(500)
                ->each(function (Inspection $inspection) use ($handle): void {
                    $user = $inspection->asset->user;
                    $department = $user->department;

                    fputcsv($handle, [
                        $inspection->id,
                        $this->csvCell($inspection->problem_id),
                        $inspection->inspection_date->toDateString(),
                        $inspection->status->value,
                        $inspection->category->value,
                        $inspection->sub_category?->value,
                        $this->csvCell($inspection->remarks),
                        $inspection->asset_id,
                        $this->csvCell($inspection->asset->asset_tag),
                        $inspection->asset->type->value,
                        $this->csvCell($inspection->asset->brand),
                        $this->csvCell($inspection->asset->model),
                        $this->csvCell($inspection->asset->serial_number),
                        $user->id,
                        $this->csvCell($user->name),
                        $this->csvCell($user->email),
                        $department?->id,
                        $this->csvCell($department?->name),
                        $inspection->technical_personnel_id,
                        $this->csvCell($inspection->technicalPersonnel?->name),
                        $inspection->created_by,
                        $this->csvCell($inspection->createdBy->name),
                        $inspection->created_at?->toIso8601String(),
                    ], ',', '"', '');
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @return Builder<Asset>
     */
    private function assetQuery(ReportRequest $request): Builder
    {
        $query = Asset::query()
            ->when($request->validated('type'), fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->whereHas(
                'user',
                fn (Builder $userQuery) => $userQuery->where('department_id', $departmentId),
            ))
            ->when($request->validated('user_id'), fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('asset_tag', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            });

        return $query;
    }

    /**
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, granularity: string, requested_range: string}  $period
     * @return Builder<Inspection>
     */
    private function inspectionQuery(ReportRequest $request, array $period): Builder
    {
        return Inspection::query()
            ->whereDate('inspection_date', '>=', $period['from']->toDateString())
            ->whereDate('inspection_date', '<=', $period['to']->toDateString())
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->validated('category'), fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($request->validated('technical_personnel_id'), fn (Builder $query, int $personnelId) => $query->where('technical_personnel_id', $personnelId))
            ->when($request->validated('type'), fn (Builder $query, string $type) => $query->whereHas(
                'asset',
                fn (Builder $assetQuery) => $assetQuery->where('type', $type),
            ))
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->whereHas(
                'asset.user',
                fn (Builder $userQuery) => $userQuery->where('department_id', $departmentId),
            ))
            ->when($request->validated('user_id'), fn (Builder $query, int $userId) => $query->whereHas(
                'asset',
                fn (Builder $assetQuery) => $assetQuery->where('user_id', $userId),
            ))
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('problem_id', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('asset', fn (Builder $assetQuery) => $assetQuery
                            ->where('asset_tag', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%")
                            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")))
                        ->orWhereHas('technicalPersonnel', fn (Builder $personnelQuery) => $personnelQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * @return Builder<User>
     */
    private function userQuery(ReportRequest $request): Builder
    {
        return User::query()
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->validated('user_id'), fn (Builder $query, int $userId) => $query->whereKey($userId));
    }

    /**
     * @param  Builder<Asset|Inspection>  $query
     * @param  array<int, AssetType|InspectionCategory|InspectionStatus>  $cases
     * @return array<int, array<string, string|int>>
     */
    private function enumCounts(Builder $query, string $column, array $cases, string $key): array
    {
        $counts = (clone $query)
            ->select($column)
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column);

        return array_map(
            static fn (AssetType|InspectionCategory|InspectionStatus $case): array => [
                $key => $case->value,
                'name' => $case->label(),
                'label' => $case->label(),
                'count' => (int) ($counts[$case->value] ?? 0),
            ],
            $cases,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function countForCase(array $rows, string $value): int
    {
        foreach ($rows as $row) {
            if (in_array($value, [$row['status'] ?? null, $row['category'] ?? null], true)) {
                return (int) $row['count'];
            }
        }

        return 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ramUsageByDepartment(ReportRequest $request): array
    {
        $ramByDepartment = $this->assetQuery($request)
            ->join('users', 'users.id', '=', 'assets.user_id')
            ->whereNotNull('assets.ram_gb')
            ->whereNotNull('users.department_id')
            ->groupBy('users.department_id')
            ->select('users.department_id')
            ->selectRaw('SUM(assets.ram_gb) AS total_ram_gb')
            ->selectRaw('AVG(assets.ram_gb) AS average_ram_gb')
            ->selectRaw('COUNT(*) AS asset_count')
            ->get()
            ->keyBy('department_id');

        return Department::query()
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->whereKey($departmentId))
            ->orderBy('name')
            ->get()
            ->map(function (Department $department) use ($ramByDepartment): array {
                $metrics = $ramByDepartment->get($department->getKey());
                $totalRamGb = round((float) ($metrics?->total_ram_gb ?? 0), 2);
                $averageRamGb = round((float) ($metrics?->average_ram_gb ?? 0), 2);

                return [
                    'department_id' => $department->id,
                    'department' => $department->name,
                    'name' => $department->name,
                    'ram_gb' => $totalRamGb,
                    'total_ram_gb' => $totalRamGb,
                    'average_ram_gb' => $averageRamGb,
                    'asset_count' => (int) ($metrics?->asset_count ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @param  Builder<Inspection>  $query
     * @param  array{from: CarbonImmutable, to: CarbonImmutable, granularity: string, requested_range: string}  $period
     * @return array<int, array<string, mixed>>
     */
    private function inspectionTrend(Builder $query, array $period): array
    {
        $counts = [];

        (clone $query)
            ->selectRaw('DATE(inspection_date) AS inspection_day')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupByRaw('DATE(inspection_date)')
            ->get()
            ->each(function (Inspection $row) use (&$counts, $period): void {
                $date = CarbonImmutable::parse((string) $row->inspection_day);
                $bucket = $period['granularity'] === 'weekly' ? $date->startOfWeek() : $date->startOfDay();
                $key = $bucket->toDateString();
                $counts[$key] = ($counts[$key] ?? 0) + (int) $row->aggregate;
            });

        $trend = [];
        $cursor = $period['granularity'] === 'weekly'
            ? $period['from']->startOfWeek()
            : $period['from']->startOfDay();

        while ($cursor->lessThanOrEqualTo($period['to'])) {
            $key = $cursor->toDateString();
            $count = $counts[$key] ?? 0;

            if ($period['granularity'] === 'weekly') {
                $weekEnd = $cursor->endOfWeek();
                $name = "{$cursor->format('M j, Y')} - {$weekEnd->format('M j, Y')}";
            } else {
                $name = $cursor->format('M j, Y');
            }

            $trend[] = [
                'period' => $key,
                'date' => $key,
                'name' => $name,
                'label' => $name,
                'count' => $count,
            ];

            $cursor = $cursor->add($period['granularity'] === 'weekly' ? '1 week' : '1 day');
        }

        return $trend;
    }

    /**
     * @param  Builder<Inspection>  $query
     * @return array<int, array<string, mixed>>
     */
    private function technicianWorkload(ReportRequest $request, Builder $query): array
    {
        $workloadByPersonnel = (clone $query)
            ->whereNotNull('technical_personnel_id')
            ->groupBy('technical_personnel_id')
            ->select('technical_personnel_id')
            ->selectRaw('COUNT(*) AS inspection_count')
            ->selectRaw('SUM(CASE WHEN category = ? THEN 1 ELSE 0 END) AS new_purchase_count', [InspectionCategory::NewPurchase->value])
            ->selectRaw('SUM(CASE WHEN category = ? THEN 1 ELSE 0 END) AS repair_count', [InspectionCategory::Repair->value])
            ->get()
            ->keyBy('technical_personnel_id');

        return TechnicalPersonnel::query()
            ->when($request->validated('technical_personnel_id'), fn (Builder $personnelQuery, int $personnelId) => $personnelQuery->whereKey($personnelId))
            ->orderBy('name')
            ->get()
            ->map(function (TechnicalPersonnel $personnel) use ($workloadByPersonnel): array {
                $workload = $workloadByPersonnel->get($personnel->getKey());

                return [
                    'technical_personnel_id' => $personnel->id,
                    'name' => $personnel->name,
                    'count' => (int) ($workload?->inspection_count ?? 0),
                    'new_purchase_count' => (int) ($workload?->new_purchase_count ?? 0),
                    'repair_count' => (int) ($workload?->repair_count ?? 0),
                ];
            })
            ->all();
    }

    private function csvCell(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'{$value}" : $value;
    }
}
