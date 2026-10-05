<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IssueType;
use Illuminate\Http\JsonResponse;

class IssueController
{
    // get all issues
    public function getIssues(Request $request): JsonResponse
    {
        $presentIssues = IssueType::all();

        return response()->json(
            [
                'status' => 'success',
                'data' => $presentIssues
            ],
            200
        );
    }

    // post 1 issue
    public function postIssues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $issue = IssueType::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $issue
        ], 201);
    }


    // get issue by id
    public function getIssueById(Request $request): JsonResponse
    {
        $issue = IssueType::find($request->id);

        if (!$issue) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Issue not found'
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $issue
            ],
            200
        );
    }

    // update issue by id
    public function updateIssueById(Request $request): JsonResponse
    {
        $issue = IssueType::find($request->id);

        if (!$issue) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Issue not found'
                ],
                404
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $issue->update($validated);

        return response()->json(
            [
                'status' => 'success',
                'data' => $issue
            ],
            200
        );
    }


    // delete issue by id
    public function deleteIssueById(Request $request): JsonResponse
    {
        // findOrFail: handles 404
        $issue = IssueType::findOrFail($request->id);
        $issue->delete();

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Issue deleted successfully'
            ],
            200
        );
    }

}