<?php

namespace App\Console\Commands;

use App\Services\RoutePermissionService;
use Illuminate\Console\Command;

class ScanPermissions extends Command
{
    protected $signature = 'permissions:scan';

    protected $description = 'Scan all named routes and create missing permissions. Gives all permissions to Admin role.';

    public function handle(RoutePermissionService $service): int
    {
        $created = $service->sync();

        $this->info("Permission scan completed. {$created} new permission(s) created.");

        return Command::SUCCESS;
    }
}
