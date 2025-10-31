<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PermissionsEnum::cases() as $permissionEnum) {
            Permission::firstOrCreate([
                'name' => $permissionEnum->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (RolesEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate([
                'name' => $roleEnum->value,
                'guard_name' => 'web',
            ]);

            if ($roleEnum === RolesEnum::SUPERADMIN) {
                $permissions = Permission::where('guard_name', 'web')->get();
                $role->syncPermissions($permissions);
            } elseif ($roleEnum === RolesEnum::ADMIN) {
                $permissions = Permission::where('guard_name', 'web')
                    ->whereNotIn('name', [PermissionsEnum::DELETE_USER->value])
                    ->get();
                $role->syncPermissions($permissions);
            }
        }
    }
}
