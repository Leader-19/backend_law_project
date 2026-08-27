<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        $normalRole = Role::firstOrCreate(['name' => 'Normal']);
        // Normal users may open the dashboard, but its category query still
        // limits them to categories assigned directly or through their team.
        $dashboardPermission = Permission::firstOrCreate([
            'name' => 'dashboard.view',
            'guard_name' => 'web',
        ]);
        $normalRole->givePermissionTo($dashboardPermission);

    }
}
