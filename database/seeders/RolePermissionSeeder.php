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
            ['name' => 'view dashboard', 'description' => 'Melihat halaman dashboard Admin'],
            ['name' => 'view proses pengeringan', 'description' => 'Melihat halaman proses pengeringan'],
            ['name' => 'view validasi', 'description' => 'Melihat halaman validasi'],
            ['name' => 'view data jenis gabah', 'description' => 'Melihat halaman data jenis gabah'],
            ['name' => 'view data perangkat', 'description' => 'Melihat halaman data perangkat'],

            ['name' => 'view role', 'description' => 'Melihat data role'],
            ['name' => 'create role', 'description' => 'Menambah data role'],
            ['name' => 'edit role', 'description' => 'Mengubah data role'],
            ['name' => 'delete role', 'description' => 'Menghapus data role'],

            ['name' => 'view riwayat pengeringan', 'description' => 'Melihat data riwayat pengeringan'],
            ['name' => 'view informasi umum', 'description' => 'Melihat data informasi umum'],

            ['name' => 'view data pemesanan alat', 'description' => 'Melihat data pemesanan alat IoT'],

            ['name' => 'view data warehouses', 'description' => 'Melihat data gudang'],
            
            ['name' => 'view data bed dryer', 'description' => 'Melihat data bed dryer'],
            
        ];

        // Menambahkan permission dengan deskripsi
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], ['description' => $permission['description']]);
        }

        // Role Admin
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        // $adminRole->givePermissionTo(array_column($permissions, 'name'));
        $adminRole->givePermissionTo(['view dashboard', 'view role', 'view informasi umum', 'view data pemesanan alat']);

        // Role adminMitra
        $adminMitra = Role::firstOrCreate(['name' => 'Admin Mitra']);
        $adminMitra->givePermissionTo(['view dashboard', 'view proses pengeringan', 'view validasi', 'view data jenis gabah', 'view data perangkat', 'view riwayat pengeringan', 'view data warehouses', 'view data bed dryer']);

    }
}