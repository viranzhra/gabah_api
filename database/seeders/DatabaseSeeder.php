<?php

namespace Database\Seeders;

use App\Models\GrainType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(SensorDeviceSeeder::class);
        $this->call(GrainTypeSeeder::class);

        // User::factory(10)->create();

        // Membuat akun admin
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'), // Password yang di-hash
        ]);
        // Memberikan role Admin ke akun admin
        $admin->assignRole('Administrator');

        // Membuat akun Siswa Baru
        $operator = User::factory()->create([
            'name' => 'Syzahra',
            'email' => 'syzahra@gmail.com',
            'password' => bcrypt('syzahra123'), // Password yang di-hash
        ]);
        // Memberikan role Siswa Baru
        $operator->assignRole('Operator');
    }
}
