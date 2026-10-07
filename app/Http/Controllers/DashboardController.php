<?php

namespace App\Http\Controllers;

use App\Models\SupportLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function userPerformance(): JsonResponse
    {

        logger("userPerformance called");

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
}