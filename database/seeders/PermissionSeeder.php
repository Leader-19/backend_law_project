<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\RoutePermissionService;

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
