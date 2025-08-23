<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaketHargaSeeder extends Seeder
{
    /**
     * Jalankan seeder database.
     */
    public function run(): void
    {
        DB::table('paket_harga')->insert([
            [
                'nama_paket'   => 'Paket Basic',
                'harga'        => 500000,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now(),
            ],
            [
                'nama_paket'   => 'Paket Premium',
                'harga'        => 1500000,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now(),
            ],
            [
                'nama_paket'   => 'Paket Ultimate',
                'harga'        => 3000000,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now(),
            ],
        ]);
    }
}
