<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PushSubscriptionController
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Push notifications enabled.',
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
        ]);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json([
            'status' => 'success',
            'message' => 'Push notifications disabled.',
        ]);
    }
}
