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
                'user_id' => 2,
                'nama_jenis' => 'IR64',
                'deskripsi' => 'Gabah dengan varietas padi unggul yang cepat panen, hasil tinggi, dan rasanya pulen.'
            ],
        ];

        foreach ($grainTypes as $type) {
            GrainType::create($type);
        }
    }
}
