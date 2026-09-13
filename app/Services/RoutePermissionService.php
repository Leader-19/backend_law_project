<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoutePermissionService
{
    /** Create permissions from named resource routes and give every permission to Admin. */
    public function sync(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $actions = [
            'index' => 'view', 'show' => 'view',
            'create' => 'create', 'store' => 'create', 'scan' => 'create',
            'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete',
            'bulk-destroy' => 'delete', 'clear' => 'delete',
            'download' => 'download',
            'approve' => 'approve', 'reject' => 'reject',
        ];
        $aliases = [
            'documents' => 'document',
            'categories' => 'category',
            'frontend-users' => 'users',
        ];
        $created = 0;

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name) {
                continue;
            }

            $parts = explode('.', $name);
            $resource = $parts[0];
            $action = count($parts) === 1 ? 'view' : end($parts);

            // A single named GET route (for example `dashboard`) represents
            // a view permission. Resource routes use their final action.
            if (count($parts) > 1 && ! isset($actions[$action])) {
                continue;
            }

            $permission = ($aliases[$resource] ?? $resource).'.'.($actions[$action] ?? 'view');
            $model = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $created += $model->wasRecentlyCreated ? 1 : 0;
        }

        // Ensure manually-referenced permissions exist
        $manualPermissions = [
            'dashboard.view',
            'log.view',
            'activity.view', 'activity.delete',
            'backup.view', 'backup.download',
            'plans.view', 'plans.edit',
            'payments.view', 'payments.approve', 'payments.reject', 'payments.edit', 'payments.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',

            // D-LMS feature permissions
            'library.view', 'library.manage',
            'quiz.view', 'quiz.create', 'quiz.edit', 'quiz.delete',
            'certificate.view', 'certificate.manage', 'certificate.delete',
            'contact.view', 'contact.reply', 'contact.delete',
            'leaderboard.view',
            'reading-history.view',
            'user-approval.view', 'user-approval.manage',
        ];

        foreach ($manualPermissions as $perm) {
            $model = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $created += $model->wasRecentlyCreated ? 1 : 0;
        }

        Role::firstOrCreate(['name' => 'Admin'])->syncPermissions(Permission::all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }
}
