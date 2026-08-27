<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Keep the local development administrator usable whenever seeders
        // are run, including after the account already exists.
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin@12345'),
                'email_verified_at' => now(),
            ]
        );

        $allRoles = Role::pluck('name')->all();
        $admin->syncRoles($allRoles);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

    }
}
