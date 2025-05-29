<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request): JsonResponse
    {
        // Get logs for the authenticated user
        $query = ActivityLog::with('user')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');
        
        // Apply filters if provided
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }
        
        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        
        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Paginate the results
        $perPage = $request->per_page ?? 15;
        $logs = $query->paginate($perPage);
        
        return response()->json($logs);
    }

    /**
     * Get activity log summary statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        // Get logs for the authenticated user
        $userId = $request->user()->id;
        
        // Get activity counts by type
        $actionCounts = ActivityLog::where('user_id', $userId)
            ->selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();
        
        // Get entity type counts
        $entityCounts = ActivityLog::where('user_id', $userId)
            ->whereNotNull('entity_type')
            ->selectRaw('entity_type, count(*) as count')
            ->groupBy('entity_type')
            ->pluck('count', 'entity_type')
            ->toArray();
        
        // Get recent activity count (last 7 days)
        $recentCount = ActivityLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        // Get total activity count
        $totalCount = ActivityLog::where('user_id', $userId)->count();
        
        return response()->json([
            'action_counts' => $actionCounts,
            'entity_counts' => $entityCounts,
            'recent_count' => $recentCount,
            'total_count' => $totalCount,
            'first_activity_date' => ActivityLog::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->value('created_at'),
            'latest_activity_date' => ActivityLog::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->value('created_at'),
        ]);
    }
    
    /**
     * Get available filters for activity logs.
     */
    public function filters(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        // Get all unique actions
        $actions = ActivityLog::where('user_id', $userId)
            ->distinct()
            ->pluck('action');
        
        // Get all unique entity types
        $entityTypes = ActivityLog::where('user_id', $userId)
            ->whereNotNull('entity_type')
            ->distinct()
            ->pluck('entity_type');
        
        return response()->json([
            'actions' => $actions,
            'entity_types' => $entityTypes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
