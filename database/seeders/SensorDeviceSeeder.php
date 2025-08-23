<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\BedDryer;
use App\Models\SensorDevice;

class SensorDeviceSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Pastikan owner (admin) ada
        $owner = User::where('email', 'mitra1@gmail.com')->first() ?? User::first();
        if (!$owner) {
            throw new \RuntimeException('User owner tidak ditemukan. Jalankan UserSeeder dulu.');
        }

        /**
         * ================================
         * 1 GUDANG & 1 BED DRYER
         * ================================
         */
        // Gudang milik user owner
        $warehouse = Warehouse::firstOrCreate(
            ['user_id' => $owner->id, 'nama' => 'Gudang A'],
            ['deskripsi' => 'Gudang utama untuk pengujian & operasional']
        );

        // Satu bed dryer di dalam gudang tersebut
        $bedDryer = BedDryer::firstOrCreate(
            [
                'user_id'      => $owner->id,
                'warehouse_id' => $warehouse->warehouse_id,
                'nama'         => 'Bed Dryer Utama',
            ],
            ['deskripsi' => 'Unit utama untuk pengujian']
        );

        /**
         * ================================
         * 5 DEVICE DALAM 1 BED DRYER
         * - 4 Tombak (sensor gabah/ruangan/kadar air per titik)
         * - 1 Pembakaran & Pengaduk (suhu pembakaran + status agitator)
         * ================================
         *
         * Catatan:
         * - device_id dibuat unik/konstan agar id tidak berubah saat seeding ulang.
         * - address disesuaikan dengan topik MQTT yang kamu pakai.
         */
        $devices = [
            [
                'device_id'   => 1,
                'device_name' => 'Tombak 1',
                'address'     => 'iot/sensor/datagabah/1',
                'location'    => 'Sudut Kiri Depan',
                'status'      => true,
            ],
            [
                'device_id'   => 2,
                'device_name' => 'Tombak 2',
                'address'     => 'iot/sensor/datagabah/2',
                'location'    => 'Sudut Kanan Depan',
                'status'      => true,
            ],
            [
                'device_id'   => 3,
                'device_name' => 'Tombak 3',
                'address'     => 'iot/sensor/datagabah/3',
                'location'    => 'Sudut Kiri Belakang',
                'status'      => true,
            ],
            [
                'device_id'   => 4,
                'device_name' => 'Tombak 4',
                'address'     => 'iot/sensor/datagabah/4',
                'location'    => 'Sudut Kanan Belakang',
                'status'      => true,
            ],
            [
                'device_id'   => 5,
                'device_name' => 'Pembakaran & Pengaduk',
                'address'     => 'iot/sensor/pembakaran/5',
                'location'    => 'Pipa Blower / Pemanas',
                'status'      => true,
            ],
        ];

        foreach ($devices as $d) {
            SensorDevice::updateOrCreate(
                // Kunci unik device (ubah jika schema kamu beda)
                ['device_id' => $d['device_id']],
                [
                    'dryer_id'    => $bedDryer->dryer_id,
                    'device_name' => $d['device_name'],
                    'address'     => $d['address'],
                    'location'    => $d['location'],
                    'status'      => (bool) $d['status'],
                ]
            );
        }
    }
}
