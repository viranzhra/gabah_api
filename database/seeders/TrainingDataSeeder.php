<?php

namespace Database\Seeders;

use App\Models\TrainingGroup;
use App\Models\TrainingData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Kelompok 1–8 (Dataset sebelumnya)
            [
                [
                    ["grain_temperature" => 28.0, "grain_moisture" => 30.0, "room_temperature" => 30.0, "weight" => 2000.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 30.0, "room_temperature" => 30.0, "weight" => 2000.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 29.0, "room_temperature" => 31.0, "weight" => 2000.0],
                    ["grain_temperature" => 30.0, "grain_moisture" => 29.0, "room_temperature" => 31.0, "weight" => 2000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 29.0, "room_temperature" => 31.0, "weight" => 2000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 2000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 2000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 2000.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 27.0, "room_temperature" => 33.0, "weight" => 2000.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 27.0, "room_temperature" => 33.0, "weight" => 2000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 27.0, "room_temperature" => 33.0, "weight" => 2000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 2000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 2000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 2000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 25.0, "room_temperature" => 35.0, "weight" => 2000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 25.0, "room_temperature" => 35.0, "weight" => 2000.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 25.0, "room_temperature" => 36.0, "weight" => 2000.0]
                ],
                ["drying_time" => 480]
            ],
            [
                [
                    ["grain_temperature" => 40.0, "grain_moisture" => 30.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 30.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 29.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 29.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 28.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 28.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 27.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 27.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 26.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 26.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 25.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 25.0, "room_temperature" => 40.0, "weight" => 2000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 25.0, "room_temperature" => 40.0, "weight" => 2000.0]
                ],
                ["drying_time" => 360]
            ],
            [
                [
                    ["grain_temperature" => 28.0, "grain_moisture" => 16.0, "room_temperature" => 30.0, "weight" => 2000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 15.0, "room_temperature" => 31.0, "weight" => 2000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 14.0, "room_temperature" => 33.0, "weight" => 2000.0]
                ],
                ["drying_time" => 60]
            ],
            [
                [
                    ["grain_temperature" => 40.0, "grain_moisture" => 14.0, "room_temperature" => 40.0, "weight" => 2000.0]
                ],
                ["drying_time" => 0]
            ],
            [
                [
                    ["grain_temperature" => 28.0, "grain_moisture" => 30.0, "room_temperature" => 30.0, "weight" => 3000.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 30.0, "room_temperature" => 30.0, "weight" => 3000.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 29.0, "room_temperature" => 31.0, "weight" => 3000.0],
                    ["grain_temperature" => 30.0, "grain_moisture" => 29.0, "room_temperature" => 31.0, "weight" => 3000.0],
                    ["grain_temperature" => 30.0, "grain_moisture" => 29.0, "room_temperature" => 31.0, "weight" => 3000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 3000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 3000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 3000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 28.0, "room_temperature" => 32.0, "weight" => 3000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 27.0, "room_temperature" => 33.0, "weight" => 3000.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 27.0, "room_temperature" => 33.0, "weight" => 3000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 27.0, "room_temperature" => 33.0, "weight" => 3000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 3000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 3000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 3000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 25.0, "room_temperature" => 35.0, "weight" => 3000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 25.0, "room_temperature" => 35.0, "weight" => 3000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 25.0, "room_temperature" => 35.0, "weight" => 3000.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 25.0, "room_temperature" => 36.0, "weight" => 2000.0]
                ],
                ["drying_time" => 540]
            ],
            [
                [
                    ["grain_temperature" => 20.4, "grain_moisture" => 17.0, "room_temperature" => 32.5, "weight" => 2000.0],
                    ["grain_temperature" => 21.1, "grain_moisture" => 17.2, "room_temperature" => 33.0, "weight" => 2000.0],
                    ["grain_temperature" => 22.0, "grain_moisture" => 17.5, "room_temperature" => 32.0, "weight" => 2000.0],
                    ["grain_temperature" => 23.5, "grain_moisture" => 16.8, "room_temperature" => 31.5, "weight" => 2000.0]
                ],
                ["drying_time" => 400]
            ],
            [
                [
                    ["grain_temperature" => 28.0, "grain_moisture" => 28.0, "room_temperature" => 30.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 27.8, "room_temperature" => 30.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 29.5, "grain_moisture" => 27.5, "room_temperature" => 30.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 30.0, "grain_moisture" => 27.0, "room_temperature" => 30.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 30.5, "grain_moisture" => 26.5, "room_temperature" => 31.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 26.0, "room_temperature" => 31.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 31.5, "grain_moisture" => 25.5, "room_temperature" => 31.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 25.0, "room_temperature" => 31.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 32.5, "grain_moisture" => 24.5, "room_temperature" => 32.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 24.0, "room_temperature" => 32.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 23.5, "room_temperature" => 32.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 33.5, "grain_moisture" => 23.0, "room_temperature" => 32.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 33.5, "grain_moisture" => 22.0, "room_temperature" => 33.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 21.0, "room_temperature" => 33.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 20.0, "room_temperature" => 33.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 34.5, "grain_moisture" => 19.0, "room_temperature" => 33.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 34.5, "grain_moisture" => 18.0, "room_temperature" => 34.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 17.0, "room_temperature" => 34.0, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 16.0, "room_temperature" => 34.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 35.5, "grain_moisture" => 15.0, "room_temperature" => 34.5, "weight" => 20000.0, "combustion_temperature" => 300.0],
                    ["grain_temperature" => 35.5, "grain_moisture" => 14.0, "room_temperature" => 35.0, "weight" => 20000.0, "combustion_temperature" => 300.0]
                ],
                ["drying_time" => 1200]
            ],
            [
                [
                    ["grain_temperature" => 30.0, "grain_moisture" => 30.0, "room_temperature" => 28.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 29.5, "room_temperature" => 28.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 29.0, "room_temperature" => 28.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 28.5, "room_temperature" => 28.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 28.0, "room_temperature" => 29.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 27.5, "room_temperature" => 29.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 27.0, "room_temperature" => 29.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 26.0, "room_temperature" => 29.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 25.0, "room_temperature" => 30.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 24.0, "room_temperature" => 30.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 23.0, "room_temperature" => 30.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 41.0, "grain_moisture" => 22.0, "room_temperature" => 30.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 42.0, "grain_moisture" => 21.0, "room_temperature" => 31.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 43.0, "grain_moisture" => 20.0, "room_temperature" => 31.0, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 44.0, "grain_moisture" => 19.0, "room_temperature" => 31.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 45.0, "grain_moisture" => 18.0, "room_temperature" => 31.5, "weight" => 2000.0, "combustion_temperature" => 250.0],
                    ["grain_temperature" => 46.0, "grain_moisture" => 17.0, "room_temperature" => 32.0, "weight" => 2000.0, "combustion_temperature" => 250.0]
                ],
                ["drying_time" => 60]
            ],
            // Kelompok 9 (Berdasarkan jurnal UGM, pengeringan bak datar, suhu 41.36°C, waktu 9 jam)[](https://jurnal.ugm.ac.id/agritech/article/view/19212)
            [
                [
                    ["grain_temperature" => 34.0, "grain_moisture" => 25.0, "room_temperature" => 38.0, "weight" => 10000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 23.9, "room_temperature" => 38.5, "weight" => 10000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 22.8, "room_temperature" => 39.0, "weight" => 10000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 21.7, "room_temperature" => 39.5, "weight" => 10000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 20.6, "room_temperature" => 40.0, "weight" => 10000.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 19.5, "room_temperature" => 40.5, "weight" => 10000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 18.4, "room_temperature" => 41.0, "weight" => 10000.0],
                    ["grain_temperature" => 41.0, "grain_moisture" => 17.3, "room_temperature" => 41.3, "weight" => 10000.0],
                    ["grain_temperature" => 41.4, "grain_moisture" => 16.2, "room_temperature" => 41.4, "weight" => 10000.0],
                    ["grain_temperature" => 41.4, "grain_moisture" => 15.1, "room_temperature" => 41.4, "weight" => 10000.0],
                    ["grain_temperature" => 41.4, "grain_moisture" => 14.4, "room_temperature" => 41.4, "weight" => 10000.0]
                ],
                ["drying_time" => 540] // 9 jam = 540 menit
            ],
            // Kelompok 10 (Berdasarkan jurnal, pengeringan 10 kg, suhu 34–38°C, 9 hari tanpa blower)[](https://text-id.123dok.com/document/wye8927y7-laju-pengeringan-grafik-penyusutan-berat-gabah.html)
            [
                [
                    ["grain_temperature" => 34.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 10000.0],
                    ["grain_temperature" => 34.5, "grain_moisture" => 25.5, "room_temperature" => 34.5, "weight" => 10000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 25.0, "room_temperature" => 35.0, "weight" => 10000.0],
                    ["grain_temperature" => 35.5, "grain_moisture" => 24.5, "room_temperature" => 35.5, "weight" => 10000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 24.0, "room_temperature" => 36.0, "weight" => 10000.0],
                    ["grain_temperature" => 36.5, "grain_moisture" => 23.5, "room_temperature" => 36.5, "weight" => 10000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 23.0, "room_temperature" => 37.0, "weight" => 10000.0],
                    ["grain_temperature" => 37.5, "grain_moisture" => 22.5, "room_temperature" => 37.5, "weight" => 10000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 22.0, "room_temperature" => 38.0, "weight" => 10000.0]
                ],
                ["drying_time" => 3780] // 9 hari x 7 jam/hari = 63 jam = 3780 menit
            ],
            // Kelompok 11 (Berdasarkan jurnal, pengeringan 11 kg, suhu 36–45°C, 9 jam dengan blower)[](https://text-id.123dok.com/document/wye8927y7-laju-pengeringan-grafik-penyusutan-berat-gabah.html)
            [
                [
                    ["grain_temperature" => 36.0, "grain_moisture" => 24.0, "room_temperature" => 36.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 23.5, "room_temperature" => 37.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 23.0, "room_temperature" => 38.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 22.5, "room_temperature" => 39.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 22.0, "room_temperature" => 40.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 41.0, "grain_moisture" => 21.5, "room_temperature" => 41.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 42.0, "grain_moisture" => 21.0, "room_temperature" => 42.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 43.0, "grain_moisture" => 20.5, "room_temperature" => 43.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 44.0, "grain_moisture" => 20.0, "room_temperature" => 44.0, "weight" => 11000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 45.0, "grain_moisture" => 19.5, "room_temperature" => 45.0, "weight" => 11000.0, "combustion_temperature" => 200.0]
                ],
                ["drying_time" => 540] // 9 jam
            ],
            // Kelompok 12 (Berdasarkan jurnal, fluidized bed dryer, suhu rendah 40°C)[](https://www.academia.edu/85715613/Perhitungan_Efisiensi_Pengeringan_pada_Mesin_Pengering_Gabah_Tipe_Flat_Bed_Dryer_di_CV_XYZ)
            [
                [
                    ["grain_temperature" => 40.0, "grain_moisture" => 26.0, "room_temperature" => 40.0, "weight" => 5000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 25.0, "room_temperature" => 40.0, "weight" => 5000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 24.0, "room_temperature" => 40.0, "weight" => 5000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 23.0, "room_temperature" => 40.0, "weight" => 5000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 22.0, "room_temperature" => 40.0, "weight" => 5000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 21.0, "room_temperature" => 40.0, "weight" => 5000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 20.0, "room_temperature" => 40.0, "weight" => 5000.0]
                ],
                ["drying_time" => 420] // 7 jam, sesuai pengeringan cepat dengan fluidized bed dryer
            ],
            // Kelompok 13 (Berdasarkan jurnal, suhu tinggi 60°C, kelembaban rendah)[](https://ejournal.unisba.ac.id/index.php/ethos/article/view/1663)
            [
                [
                    ["grain_temperature" => 50.0, "grain_moisture" => 28.0, "room_temperature" => 50.0, "weight" => 12000.0, "combustion_temperature" => 150.0],
                    ["grain_temperature" => 51.0, "grain_moisture" => 27.0, "room_temperature" => 51.0, "weight" => 12000.0, "combustion_temperature" => 150.0],
                    ["grain_temperature" => 52.0, "grain_moisture" => 26.0, "room_temperature" => 52.0, "weight" => 12000.0, "combustion_temperature" => 150.0],
                    ["grain_temperature" => 53.0, "grain_moisture" => 25.0, "room_temperature" => 53.0, "weight" => 12000.0, "combustion_temperature" => 150.0],
                    ["grain_temperature" => 54.0, "grain_moisture" => 24.0, "room_temperature" => 54.0, "weight" => 12000.0, "combustion_temperature" => 150.0],
                    ["grain_temperature" => 55.0, "grain_moisture" => 23.0, "room_temperature" => 55.0, "weight" => 12000.0, "combustion_temperature" => 150.0]
                ],
                ["drying_time" => 360] // 6 jam, karena suhu tinggi mempercepat pengeringan
            ],
            // Kelompok 14 (Berdasarkan jurnal, screw conveyor dryer, 500 kg)[](https://www.academia.edu/85715613/Perhitungan_Efisiensi_Pengeringan_pada_Mesin_Pengering_Gabah_Tipe_Flat_Bed_Dryer_di_CV_XYZ)
            [
                [
                    ["grain_temperature" => 38.0, "grain_moisture" => 25.0, "room_temperature" => 38.0, "weight" => 500000.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 24.0, "room_temperature" => 39.0, "weight" => 500000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 23.0, "room_temperature" => 40.0, "weight" => 500000.0],
                    ["grain_temperature" => 41.0, "grain_moisture" => 22.0, "room_temperature" => 41.0, "weight" => 500000.0],
                    ["grain_temperature" => 42.0, "grain_moisture" => 21.0, "room_temperature" => 42.0, "weight" => 500000.0]
                ],
                ["drying_time" => 600] // 10 jam untuk batch besar
            ],
            // Kelompok 15 (Kadar air awal tinggi, suhu rendah)
            [
                [
                    ["grain_temperature" => 30.0, "grain_moisture" => 28.0, "room_temperature" => 30.0, "weight" => 8000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 27.5, "room_temperature" => 31.0, "weight" => 8000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 27.0, "room_temperature" => 32.0, "weight" => 8000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 26.5, "room_temperature" => 33.0, "weight" => 8000.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 8000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 25.5, "room_temperature" => 35.0, "weight" => 8000.0]
                ],
                ["drying_time" => 480]
            ],
            // Kelompok 16 (Suhu tinggi, kadar air rendah)
            [
                [
                    ["grain_temperature" => 45.0, "grain_moisture" => 18.0, "room_temperature" => 45.0, "weight" => 6000.0, "combustion_temperature" => 180.0],
                    ["grain_temperature" => 46.0, "grain_moisture" => 17.5, "room_temperature" => 46.0, "weight" => 6000.0, "combustion_temperature" => 180.0],
                    ["grain_temperature" => 47.0, "grain_moisture" => 17.0, "room_temperature" => 47.0, "weight" => 6000.0, "combustion_temperature" => 180.0],
                    ["grain_temperature" => 48.0, "grain_moisture" => 16.5, "room_temperature" => 48.0, "weight" => 6000.0, "combustion_temperature" => 180.0],
                    ["grain_temperature" => 49.0, "grain_moisture" => 16.0, "room_temperature" => 49.0, "weight" => 6000.0, "combustion_temperature" => 180.0]
                ],
                ["drying_time" => 300] // 5 jam, karena suhu tinggi
            ],
            // Kelompok 17 (Berdasarkan jurnal, kadar air turun perlahan)[](https://jurnal.ugm.ac.id/agritech/article/view/19212)
            [
                [
                    ["grain_temperature" => 35.0, "grain_moisture" => 24.0, "room_temperature" => 38.0, "weight" => 15000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 23.5, "room_temperature" => 38.5, "weight" => 15000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 23.0, "room_temperature" => 39.0, "weight" => 15000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 22.5, "room_temperature" => 39.5, "weight" => 15000.0],
                    ["grain_temperature" => 39.0, "grain_moisture" => 22.0, "room_temperature" => 40.0, "weight" => 15000.0],
                    ["grain_temperature" => 40.0, "grain_moisture" => 21.5, "room_temperature" => 40.5, "weight" => 15000.0],
                    ["grain_temperature" => 41.0, "grain_moisture" => 21.0, "room_temperature" => 41.0, "weight" => 15000.0]
                ],
                ["drying_time" => 600] // 10 jam
            ],
            // Kelompok 18 (Kadar air tinggi, berat besar)
            [
                [
                    ["grain_temperature" => 32.0, "grain_moisture" => 30.0, "room_temperature" => 32.0, "weight" => 20000.0, "combustion_temperature" => 280.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 29.5, "room_temperature" => 33.0, "weight" => 20000.0, "combustion_temperature" => 280.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 29.0, "room_temperature" => 34.0, "weight" => 20000.0, "combustion_temperature" => 280.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 28.5, "room_temperature" => 35.0, "weight" => 20000.0, "combustion_temperature" => 280.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 28.0, "room_temperature" => 36.0, "weight" => 20000.0, "combustion_temperature" => 280.0]
                ],
                ["drying_time" => 720] // 12 jam
            ],
            // Kelompok 19 (Suhu rendah, berat kecil)
            [
                [
                    ["grain_temperature" => 28.0, "grain_moisture" => 22.0, "room_temperature" => 28.0, "weight" => 3000.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 21.5, "room_temperature" => 29.0, "weight" => 3000.0],
                    ["grain_temperature" => 30.0, "grain_moisture" => 21.0, "room_temperature" => 30.0, "weight" => 3000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 20.5, "room_temperature" => 31.0, "weight" => 3000.0]
                ],
                ["drying_time" => 360] // 6 jam
            ],
            // Kelompok 20 (Suhu tinggi, kadar air rendah)
            [
                [
                    ["grain_temperature" => 48.0, "grain_moisture" => 16.0, "room_temperature" => 48.0, "weight" => 4000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 49.0, "grain_moisture" => 15.5, "room_temperature" => 49.0, "weight" => 4000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 50.0, "grain_moisture" => 15.0, "room_temperature" => 50.0, "weight" => 4000.0, "combustion_temperature" => 200.0],
                    ["grain_temperature" => 51.0, "grain_moisture" => 14.5, "room_temperature" => 51.0, "weight" => 4000.0, "combustion_temperature" => 200.0]
                ],
                ["drying_time" => 240] // 4 jam
            ],
            // Kelompok 21 (Berdasarkan jurnal, fluidized bed dryer dengan tempering)[](https://www.academia.edu/85715613/Perhitungan_Efisiensi_Pengeringan_pada_Mesin_Pengering_Gabah_Tipe_Flat_Bed_Dryer_di_CV_XYZ)
            [
                [
                    ["grain_temperature" => 40.0, "grain_moisture" => 20.0, "room_temperature" => 40.0, "weight" => 10000.0],
                    ["grain_temperature" => 40.5, "grain_moisture" => 19.5, "room_temperature" => 40.5, "weight" => 10000.0],
                    ["grain_temperature" => 41.0, "grain_moisture" => 19.0, "room_temperature" => 41.0, "weight" => 10000.0],
                    ["grain_temperature" => 41.5, "grain_moisture" => 18.5, "room_temperature" => 41.5, "weight" => 10000.0],
                    ["grain_temperature" => 42.0, "grain_moisture" => 18.0, "room_temperature" => 42.0, "weight" => 10000.0]
                ],
                ["drying_time" => 480] // 8 jam dengan tempering
            ],
            // Kelompok 22 (Berat besar, suhu rendah)
            [
                [
                    ["grain_temperature" => 30.0, "grain_moisture" => 26.0, "room_temperature" => 30.0, "weight" => 15000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 25.5, "room_temperature" => 31.0, "weight" => 15000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 25.0, "room_temperature" => 32.0, "weight" => 15000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 24.5, "room_temperature" => 33.0, "weight" => 15000.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 24.0, "room_temperature" => 34.0, "weight" => 15000.0]
                ],
                ["drying_time" => 600] // 10 jam
            ],
            // Kelompok 23 (Suhu tinggi, kadar air tinggi)
            [
                [
                    ["grain_temperature" => 50.0, "grain_moisture" => 30.0, "room_temperature" => 50.0, "weight" => 8000.0, "combustion_temperature" => 160.0],
                    ["grain_temperature" => 51.0, "grain_moisture" => 29.0, "room_temperature" => 51.0, "weight" => 8000.0, "combustion_temperature" => 160.0],
                    ["grain_temperature" => 52.0, "grain_moisture" => 28.0, "room_temperature" => 52.0, "weight" => 8000.0, "combustion_temperature" => 160.0],
                    ["grain_temperature" => 53.0, "grain_moisture" => 27.0, "room_temperature" => 53.0, "weight" => 8000.0, "combustion_temperature" => 160.0]
                ],
                ["drying_time" => 420] // 7 jam
            ],
            // Kelompok 24 (Kadar air rendah, berat kecil)
            [
                [
                    ["grain_temperature" => 35.0, "grain_moisture" => 18.0, "room_temperature" => 35.0, "weight" => 2000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 17.5, "room_temperature" => 36.0, "weight" => 2000.0],
                    ["grain_temperature" => 37.0, "grain_moisture" => 17.0, "room_temperature" => 37.0, "weight" => 2000.0],
                    ["grain_temperature" => 38.0, "grain_moisture" => 16.5, "room_temperature" => 38.0, "weight" => 2000.0]
                ],
                ["drying_time" => 300] // 5 jam
            ],
            // Kelompok 25 (Suhu rendah, berat besar)
            [
                [
                    ["grain_temperature" => 28.0, "grain_moisture" => 24.0, "room_temperature" => 28.0, "weight" => 18000.0],
                    ["grain_temperature" => 29.0, "grain_moisture" => 23.5, "room_temperature" => 29.0, "weight" => 18000.0],
                    ["grain_temperature" => 30.0, "grain_moisture" => 23.0, "room_temperature" => 30.0, "weight" => 18000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 22.5, "room_temperature" => 31.0, "weight" => 18000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 22.0, "room_temperature" => 32.0, "weight" => 18000.0]
                ],
                ["drying_time" => 720] // 12 jam
            ],
            // Kelompok 26 (Suhu tinggi, kadar air sedang)
            [
                [
                    ["grain_temperature" => 45.0, "grain_moisture" => 22.0, "room_temperature" => 45.0, "weight" => 7000.0, "combustion_temperature" => 170.0],
                    ["grain_temperature" => 46.0, "grain_moisture" => 21.5, "room_temperature" => 46.0, "weight" => 7000.0, "combustion_temperature" => 170.0],
                    ["grain_temperature" => 47.0, "grain_moisture" => 21.0, "room_temperature" => 47.0, "weight" => 7000.0, "combustion_temperature" => 170.0],
                    ["grain_temperature" => 48.0, "grain_moisture" => 20.5, "room_temperature" => 48.0, "weight" => 7000.0, "combustion_temperature" => 170.0]
                ],
                ["drying_time" => 360] // 6 jam
            ],
            // Kelompok 27 (Berdasarkan jurnal, pengeringan lambat tanpa blower)[](https://text-id.123dok.com/document/wye8927y7-laju-pengeringan-grafik-penyusutan-berat-gabah.html)
            [
                [
                    ["grain_temperature" => 34.0, "grain_moisture" => 25.0, "room_temperature" => 34.0, "weight" => 10000.0],
                    ["grain_temperature" => 34.5, "grain_moisture" => 24.5, "room_temperature" => 34.5, "weight" => 10000.0],
                    ["grain_temperature" => 35.0, "grain_moisture" => 24.0, "room_temperature" => 35.0, "weight" => 10000.0],
                    ["grain_temperature" => 35.5, "grain_moisture" => 23.5, "room_temperature" => 35.5, "weight" => 10000.0],
                    ["grain_temperature" => 36.0, "grain_moisture" => 23.0, "room_temperature" => 36.0, "weight" => 10000.0]
                ],
                ["drying_time" => 3240] // 9 hari x 6 jam/hari = 54 jam = 3240 menit
            ],
            // Kelompok 28 (Suhu rendah, kadar air tinggi)
            [
                [
                    ["grain_temperature" => 30.0, "grain_moisture" => 28.0, "room_temperature" => 30.0, "weight" => 5000.0],
                    ["grain_temperature" => 31.0, "grain_moisture" => 27.5, "room_temperature" => 31.0, "weight" => 5000.0],
                    ["grain_temperature" => 32.0, "grain_moisture" => 27.0, "room_temperature" => 32.0, "weight" => 5000.0],
                    ["grain_temperature" => 33.0, "grain_moisture" => 26.5, "room_temperature" => 33.0, "weight" => 5000.0],
                    ["grain_temperature" => 34.0, "grain_moisture" => 26.0, "room_temperature" => 34.0, "weight" => 5000.0]
                ],
                ["drying_time" => 540] // 9 jam
            ]
        ];

        DB::transaction(function () use ($data) {
            foreach ($data as $groupData) {
                // Create a TrainingGroup record
                $group = TrainingGroup::create([
                    'drying_time' => $groupData[1]['drying_time'],
                ]);

                // Create TrainingData records for each measurement
                foreach ($groupData[0] as $measurement) {
                    TrainingData::create([
                        'training_group_id' => $group->id,
                        'grain_temperature' => $measurement['grain_temperature'],
                        'grain_moisture' => $measurement['grain_moisture'],
                        'room_temperature' => $measurement['room_temperature'],
                        'weight' => $measurement['weight'],
                        'combustion_temperature' => isset($measurement['combustion_temperature'])
                            ? $measurement['combustion_temperature']
                            : null,
                    ]);
                }
            }
        });
    }
}