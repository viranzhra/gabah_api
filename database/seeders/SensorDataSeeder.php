<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorData;

class SensorDataSeeder extends Seeder
{
    public function run()
    {
        $dummyData = [
            [
                'process_id' => null,
                'device_id' => 1,
                'timestamp' => '2025-07-06 16:00:00',
                'kadar_air_gabah' => 18.5,
                'suhu_gabah' => 30.2,
                'suhu_ruangan' => 25.7,
                'suhu_pembakaran' => 45.0,
            ],
            [
                'process_id' => null,
                'device_id' => 2,
                'timestamp' => '2025-07-06 16:15:00',
                'kadar_air_gabah' => 17.8,
                'suhu_gabah' => 31.0,
                'suhu_ruangan' => 26.1,
                'suhu_pembakaran' => 46.5,
            ],
            [
                'process_id' => null,
                'device_id' => 3,
                'timestamp' => '2025-07-06 16:15:00',
                'kadar_air_gabah' => 17.8,
                'suhu_gabah' => 33.0,
                'suhu_ruangan' => 26.1,
                'suhu_pembakaran' => 46.5,
            ],
            [
                'process_id' => null,
                'device_id' => 4,
                'timestamp' => '2025-07-06 16:15:00',
                'kadar_air_gabah' => 17.8,
                'suhu_gabah' => 32.0,
                'suhu_ruangan' => 26.1,
                'suhu_pembakaran' => 50.5,
            ],
        ];

        foreach ($dummyData as $data) {
            SensorData::create($data);
        }
    }
}