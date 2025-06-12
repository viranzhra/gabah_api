<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorDevice;

class SensorDeviceSeeder extends Seeder
{
    public function run(): void
    {
        SensorDevice::insert([
            ['device_name' => 'tombak1', 'location' => 'Kanan', 'device_type' => 'grain_sensor'],
            ['device_name' => 'tombak2', 'location' => 'Kiri', 'device_type' => 'grain_sensor'],
            ['device_name' => 'tombak3', 'location' => 'Tengah Kanan', 'device_type' => 'grain_sensor'],
            ['device_name' => 'panel_room_temp', 'location' => 'Tengah Kiri', 'device_type' => 'room_sensor'],
        ]);
    }
}
