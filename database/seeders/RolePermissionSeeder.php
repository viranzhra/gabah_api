<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Data Permission beserta Deskripsi
        $permissions = [
            ['name' => 'view dashboard admin', 'description' => 'Melihat halaman dashboard Admin'],
            ['name' => 'view dashboard operator', 'description' => 'Melihat halaman dashboard Operator'],

            ['name' => 'view role', 'description' => 'Melihat data role'],
            ['name' => 'create role', 'description' => 'Menambah data role'],
            ['name' => 'edit role', 'description' => 'Mengubah data role'],
            ['name' => 'delete role', 'description' => 'Menghapus data role'],
            
        ];

        // Menambahkan permission dengan deskripsi
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], ['description' => $permission['description']]);
        }

        // Role Admin
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->givePermissionTo(array_column($permissions, 'name'));

        // Role Operator
        $operator = Role::firstOrCreate(['name' => 'Operator']);
        $operator->givePermissionTo(['view dashboard operator']);

    }
}