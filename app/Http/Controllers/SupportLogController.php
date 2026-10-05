<?php

namespace App\Http\Controllers;

use App\Models\SupportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupportLogController
{
    private const RELATIONS = [
        'department',
        'itemType',
        'issueTypes',
        'creator',
        'assignedResource',
    ];

    public function getSupportLogs(Request $request): JsonResponse
    {
        $supportLogs = SupportLog::with(self::RELATIONS)
            ->latest()
            ->get();

        logger("am i gettign supportLogs");    
        logger($supportLogs);

        return response()->json([
            'status' => 'success',
            'data'   => $supportLogs,
        ]);
    }

    public function postSupportLogs(Request $request): JsonResponse
    {
        logger("am i getting request");
        logger($request->all());

        $validated = $request->validate([
            'issue_date'       => ['required', 'date'],
            'initiated_by'     => ['required', 'string', 'max:255'],
            'department_id'    => ['required', 'exists:departments,id'],
            'item_type_id'     => ['required', 'exists:item_types,id'],
            'issue_type_ids'   => ['nullable', 'array'],
            'issue_type_ids.*' => ['integer', 'exists:issue_types,id'],
            'status'           => ['nullable', Rule::in(['indoor_repairing', 'outdoor_repairing', 'solved'])],
            'issue_details'    => ['nullable', 'string'],
            'assigned_to'      => ['nullable', 'exists:users,id'],
        ]);

        // An empty status falls back to the DB default
        if (($validated['status'] ?? null) === null) {
            unset($validated['status']);
        }

        $supportLog = DB::transaction(function () use ($validated, $request) {
            $issueTypeIds = $validated['issue_type_ids'] ?? [];
            unset($validated['issue_type_ids']);

            $validated['created_by'] = $request->user()->id;
            $validated['ticket_number'] = (string) Str::uuid(); // temporary

            $log = SupportLog::create($validated);

            $log->update([
                'ticket_number' => sprintf('SL-%s-%06d', now()->format('Y'), $log->id),
            ]);

            if ($issueTypeIds) {
                $log->issueTypes()->sync($issueTypeIds);
            }

            return $log;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $supportLog->load(self::RELATIONS),
        ], 201);
    }
    
    public function getSupportLogById(Request $request): JsonResponse
    {
        $supportLog = SupportLog::with(self::RELATIONS)->find($request->id);

        if (!$supportLog) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Support log not found'
                ],
                404
            );
        }

        return response()->json([
            'status' => 'success',
            'data'   => $supportLog,
        ]);
    }

    // ---------- updateSupportLogById
    public function updateSupportLogById(Request $request): JsonResponse
    {
        $supportLog = SupportLog::findOrFail($request->id);
        if (!$supportLog) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Support log not found'
                ],
                404
            );
        }

        $validated = $request->validate([ 
            'status' => [ 
                'required', 
                Rule::in([ 
                    'indoor_repairing', 
                    'outdoor_repairing', 
                    'solved', 
                ]), 
            ], 
        ]); 

        $supportLog->update([ 
            'status' => $validated['status'], 
        ]);
        
        return response()->json([ 'status' => 'success', 'message' => 'Support log status updated successfully', 'data' => $supportLog->load(self::RELATIONS), ]);
    }
    
    // ---------- deleteSupportLogById
    public function deleteSupportLogById(): JsonResponse
    {
        $supportLog = SupportLog::findOrFail($request->id);
        if (!$supportLog) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Support log not found'
                ],
                404
            );
        }

        $supportLog->delete();

        return response()->json([
            'status' => 'success',
            'data'   => $supportLog,
        ]);
    }
}