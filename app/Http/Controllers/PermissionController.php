<?php

namespace App\Http\Controllers;

use App\Services\RoutePermissionService;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function index()
    {
        return Inertia::render('Permissions/Index', [
            'permissions' => \Spatie\Permission\Models\Permission::query()
                ->withCount('roles')
                ->orderBy('name')
                ->get(['id', 'name', 'guard_name', 'created_at']),
        ]);
    }

    public function scan(RoutePermissionService $permissions)
    {
        $created = $permissions->sync();

        return to_route('permissions.index')
            ->with('success', "Permission scan completed. {$created} new permission(s) created.");
    }
}
