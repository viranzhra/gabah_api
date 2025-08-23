<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KontakInfo;

class KontakInfoSeeder extends Seeder
{
    public function run(): void
    {
        KontakInfo::insert([
            'alamat' => 'Jl. Inovasi No. 10, Indramayu, Jawa Barat, Indonesia',
            'telepon' => '+62 812 3456 7890',
            'email' => 'support@graindryeriot.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}