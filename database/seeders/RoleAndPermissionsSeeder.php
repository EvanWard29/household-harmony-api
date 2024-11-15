<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        foreach (PermissionsEnum::cases() as $permission) {
            Permission::updateOrCreate(['name' => $permission->value]);
        }

        // Update cache to know about the newly created permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles and assign created permissions
        foreach (RolesEnum::cases() as $role) {
            Role::updateOrCreate(['name' => $role->value]);
        }

        // Assign the `admin` role all permissions
        Role::firstWhere('name', RolesEnum::ADMIN->value)
            ->givePermissionTo(Permission::all());
    }
}
