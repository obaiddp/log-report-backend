<?php

namespace App\Http\Controllers;

use App\Enums\SupportLogPriority;
use App\Enums\SupportLogStatus;
use App\Http\Requests\StoreSupportLogRequest;
use App\Http\Requests\SupportLogIndexRequest;
use App\Http\Requests\UpdateSupportLogRequest;
use App\Http\Resources\SupportLogResource;
use App\Models\SupportLog;
use App\Models\SupportLogAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SupportLogController extends Controller
{
    public function index(SupportLogIndexRequest $request): AnonymousResourceCollection
    {
        $supportLogs = SupportLogQuery::apply(SupportLog::query(), $request->validated())
            ->with(SupportLogQuery::relations())
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return SupportLogResource::collection($supportLogs);
    }

    public function show(Request $request, SupportLog $supportLog): SupportLogResource
    {
        $this->authorize($request, 'view', $supportLog);
        $supportLog->load(SupportLogQuery::relations());

        return new SupportLogResource($supportLog);
    }

    public function store(StoreSupportLogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $issueTypeIds = $data['issue_type_ids'];
        unset($data['issue_type_ids']);

        $status = SupportLogStatus::from((string) ($data['status'] ?? SupportLogStatus::Open->value));
        $priority = SupportLogPriority::from((string) ($data['priority'] ?? SupportLogPriority::Medium->value));
        $assignedTo = $data['assigned_to'] ?? null;

        if ($request->user()->isTechnicalResource() && $assignedTo === null) {
            $assignedTo = $request->user()->getKey();
        }

        $supportLog = DB::transaction(function () use ($data, $issueTypeIds, $status, $priority, $assignedTo, $request): SupportLog {
            $supportLog = new SupportLog([
                ...$data,
                'ticket_number' => $this->generateTicketNumber(),
                'status' => SupportLogStatus::Open,
                'priority' => $priority,
                'assigned_to' => $assignedTo,
                'created_by' => $request->user()->getKey(),
            ]);
            $supportLog->issue_date = $data['issue_date'];
            $supportLog->save();
            $supportLog->issueTypes()->sync($issueTypeIds);

            if ($assignedTo !== null) {
                SupportLogAssignment::query()->create([
                    'support_log_id' => $supportLog->getKey(),
                    'assigned_to' => $assignedTo,
                    'assigned_by' => $request->user()->getKey(),
                    'assigned_at' => now(),
                ]);
            }

            if ($status !== SupportLogStatus::Open) {
                if ($status === SupportLogStatus::Closed) {
                    $supportLog->transitionTo(SupportLogStatus::Resolved);
                }
                $supportLog->transitionTo($status);
                $supportLog->save();
            }

            return $supportLog;
        });

        $supportLog->load(SupportLogQuery::relations());

        return SupportLogResource::make($supportLog)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateSupportLogRequest $request, SupportLog $supportLog): SupportLogResource
    {
        $data = $request->validated();
        $issueTypeIds = $data['issue_type_ids'] ?? null;
        unset($data['issue_type_ids']);

        if (array_key_exists('assigned_to', $data)) {
            Gate::forUser($request->user())->authorize('assign', $supportLog);
        }

        DB::transaction(function () use ($request, $supportLog, $data, $issueTypeIds): void {
            $supportLog = SupportLog::query()->lockForUpdate()->findOrFail($supportLog->getKey());
            $nextStatus = array_key_exists('status', $data)
                ? SupportLogStatus::from((string) $data['status'])
                : null;
            $nextAssignee = array_key_exists('assigned_to', $data)
                ? $data['assigned_to']
                : null;
            $assigneeChanged = array_key_exists('assigned_to', $data)
                && (string) $supportLog->assigned_to !== (string) $nextAssignee;

            if ($nextStatus !== null) {
                $supportLog->transitionTo($nextStatus);
                unset($data['status']);
            }

            unset($data['assigned_to']);
            $supportLog->fill($data);
            $supportLog->save();

            if ($issueTypeIds !== null) {
                $supportLog->issueTypes()->sync($issueTypeIds);
            }

            if ($assigneeChanged) {
                $now = now();
                $supportLog->assignments()
                    ->whereNull('unassigned_at')
                    ->update([
                        'unassigned_at' => $now,
                        'updated_at' => $now,
                    ]);

                $supportLog->assigned_to = $nextAssignee;
                $supportLog->save();

                if ($nextAssignee !== null) {
                    SupportLogAssignment::query()->create([
                        'support_log_id' => $supportLog->getKey(),
                        'assigned_to' => $nextAssignee,
                        'assigned_by' => $request->user()->getKey(),
                        'assigned_at' => $now,
                    ]);
                }
            }
        });

        $supportLog->refresh()->load(SupportLogQuery::relations());

        return new SupportLogResource($supportLog);
    }

    public function destroy(Request $request, SupportLog $supportLog): JsonResponse
    {
        Gate::forUser($request->user())->authorize('delete', $supportLog);
        $supportLog->delete();

        return response()->json(['message' => 'Support log archived successfully.']);
    }

    private function authorize(Request $request, string $ability, SupportLog $supportLog): void
    {
        Gate::forUser($request->user())->authorize($ability, $supportLog);
    }

    private function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'ITL-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
        } while (SupportLog::withTrashed()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
