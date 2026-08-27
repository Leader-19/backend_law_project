<?php

namespace Database\Seeders;

use App\Services\RoutePermissionService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(RoutePermissionService $permissions): void
    {
        $permissions->sync();
    }
}
