<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,name,email');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->has('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($logs);
    }

    /**
     * Display the specified activity log.
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user:id,name,email');

        return response()->json($activityLog);
    }

    /**
     * Get activity summary by action.
     */
    public function summary(Request $request): JsonResponse
    {
        $query = ActivityLog::query();

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $summary = $query->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json($summary);
    }

    /**
     * Get activity for a specific model.
     */
    public function forModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_type' => 'required|string|max:100',
            'model_id' => 'required|integer',
        ]);

        $logs = ActivityLog::with('user:id,name')
            ->where('model_type', $validated['model_type'])
            ->where('model_id', $validated['model_id'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    /**
     * Clean old activity logs.
     */
    public function cleanOld(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:30|max:365',
        ]);

        $cutoffDate = now()->subDays($validated['days']);

        $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();

        return response()->json([
            'message' => "{$deletedCount} entrée(s) du journal supprimée(s).",
        ]);
    }
}
