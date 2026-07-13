<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $action = $request->get('action');

        $query = ActivityLog::with('causer')->latest();

        if ($action) {
            $query->where('action', $action);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        return Inertia::render('ActivityLog/Index', [
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'filters' => [
                'action' => $action,
            ],
        ]);
    }
}
