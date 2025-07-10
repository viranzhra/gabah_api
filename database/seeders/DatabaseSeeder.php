<?php

namespace Database\Seeders;

use App\Models\GrainType;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Membuat peran
        Role::firstOrCreate(
            ['name' => 'Administrator', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );
        Role::firstOrCreate(
            ['name' => 'Operator', 'guard_name' => 'web'],
            ['guard_name' => 'web']
        );

        // Membuat akun admin
        $admin = User::factory()->create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Administrator');

        // Membuat akun operator
        $operator = User::factory()->create([
            'id' => 2,
            'name' => 'Syzahra',
            'email' => 'syzahra@gmail.com',
            'password' => bcrypt('syzahra123'),
            'email_verified_at' => now(),
        ]);
        $operator->assignRole('Operator');

        // Panggil seeder lain
        $this->call([
            RolePermissionSeeder::class,
            RoleSeeder::class, // Tetap panggil untuk logika tambahan
            SensorDeviceSeeder::class,
            SensorDataSeeder::class,
            GrainTypeSeeder::class,
            TrainingDataSeeder::class,
        ]);
    }
}