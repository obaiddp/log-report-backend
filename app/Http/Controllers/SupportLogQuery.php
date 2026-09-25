<?php

namespace App\Http\Controllers;

use App\Models\SupportLog;
use Illuminate\Database\Eloquent\Builder;

final class SupportLogQuery
{
    /**
     * @param  Builder<SupportLog>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<SupportLog>
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return $query
            ->when($dateFrom, fn (Builder $builder, string $from): Builder => $builder->whereDate('support_logs.issue_date', '>=', $from))
            ->when($dateTo, fn (Builder $builder, string $to): Builder => $builder->whereDate('support_logs.issue_date', '<=', $to))
            ->when($filters['department_id'] ?? null, fn (Builder $builder, int $departmentId): Builder => $builder->where('support_logs.department_id', $departmentId))
            ->when($filters['item_type_id'] ?? null, fn (Builder $builder, int $itemTypeId): Builder => $builder->where('support_logs.item_type_id', $itemTypeId))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status): Builder => $builder->where('support_logs.status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $builder, string $priority): Builder => $builder->where('support_logs.priority', $priority))
            ->when($filters['assigned_to'] ?? null, fn (Builder $builder, int $assignedTo): Builder => $builder->where('support_logs.assigned_to', $assignedTo))
            ->when($filters['initiated_by'] ?? null, fn (Builder $builder, string $initiatedBy): Builder => $builder->whereRaw('LOWER(support_logs.initiated_by) LIKE ?', ['%'.mb_strtolower($initiatedBy).'%']))
            ->when($filters['ticket_number'] ?? null, fn (Builder $builder, string $ticketNumber): Builder => $builder->whereRaw('LOWER(support_logs.ticket_number) LIKE ?', ['%'.mb_strtolower($ticketNumber).'%']))
            ->when($filters['issue_type_id'] ?? null, fn (Builder $builder, int $issueTypeId): Builder => $builder->whereHas(
                'issueTypes',
                fn (Builder $issueQuery): Builder => $issueQuery->whereKey($issueTypeId),
            ))
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $term = '%'.$search.'%';
                $builder->where(function (Builder $searchQuery) use ($term): void {
                    $searchQuery
                        ->whereRaw('LOWER(support_logs.ticket_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(support_logs.initiated_by) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(support_logs.description) LIKE ?', [$term])
                        ->orWhereHas('department', fn (Builder $departmentQuery): Builder => $departmentQuery->whereRaw('LOWER(name) LIKE ?', [$term]))
                        ->orWhereHas('itemType', fn (Builder $itemQuery): Builder => $itemQuery->whereRaw('LOWER(name) LIKE ?', [$term]))
                        ->orWhereHas('issueTypes', fn (Builder $issueQuery): Builder => $issueQuery->whereRaw('LOWER(name) LIKE ?', [$term]))
                        ->orWhereHas('assignedTo', fn (Builder $userQuery): Builder => $userQuery->whereRaw('LOWER(name) LIKE ?', [$term]));
                });
            });
    }

    /**
     * @param  array<int, string>  $relations
     * @return array<int, string>
     */
    public static function relations(array $relations = []): array
    {
        return array_values(array_unique(array_merge([
            'department',
            'issueTypes',
            'itemType',
            'assignedTo.department',
            'creator.department',
            'assignments.assignedResource.department',
            'assignments.assigner.department',
        ], $relations)));
    }
}
