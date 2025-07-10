<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorDevice;

class SensorDeviceSeeder extends Seeder
{
    public function run(): void
    {
        SensorDevice::insert([
            ['device_name' => 'tombak1', 'deskripsi' => 'Kanan', 'status' => 'aktif'],
            ['device_name' => 'tombak2', 'deskripsi' => 'Kiri', 'status' => 'aktif'],
            ['device_name' => 'tombak3', 'deskripsi' => 'Tengah Kanan', 'status' => 'aktif'],
            ['device_name' => 'tombak4', 'deskripsi' => 'Tengah Kiri', 'status' => 'tidak_aktif'],
        ]);
    }
}