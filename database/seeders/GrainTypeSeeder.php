<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GrainType;

class GrainTypeSeeder extends Seeder
{
    /**
     * Jalankan seeder database.
     */
    public function run(): void
    {
        $grainTypes = [
            [
                'nama_jenis' => 'IR64',
                'deskripsi' => 'Gabah dengan varietas padi unggul yang cepat panen, hasil tinggi, dan rasanya pulen.'
            ],
            // [
            //     'nama_jenis' => 'Gabah Kering Giling',
            //     'deskripsi' => 'Gabah yang siap digiling dengan kadar air yang rendah.'
            // ],
            // [
            //     'nama_jenis' => 'Jagung Pipil',
            //     'deskripsi' => 'Jagung yang sudah dipipil dari tongkolnya.'
            // ],
            // [
            //     'nama_jenis' => 'Beras Merah',
            //     'deskripsi' => 'Jenis beras dengan nutrisi tinggi dan warna kemerahan.'
            // ],
        ];

        foreach ($grainTypes as $type) {
            GrainType::create($type);
        }
    }
}
