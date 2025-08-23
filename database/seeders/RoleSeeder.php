<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::firstOrCreate(
            ['name' => 'Administrator', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'Admin Mitra', 'guard_name' => 'web']
        );
    }
}
