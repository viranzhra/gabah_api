<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TrainingDataImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $path = $request->file('file')->getRealPath();
        $data = Excel::toArray([], $path)[0]; // Ambil sheet pertama

        // Buang header
        $header = array_shift($data);

        // Mapping kolom header Excel ke nama variabel
        $mappedData = [];
        foreach ($data as $row) {
            $mappedData[] = [
                'drying_time'           => (int)$row[0], // dari Waktu (detik)
                'grain_moisture'        => (float)$row[2],
                'grain_temperature'     => (float)$row[3],
                'room_temperature'      => (float)$row[4],
                'combustion_temperature'=> isset($row[5]) ? (float)$row[5] : null,
                'weight'                => (float)$row[6],
            ];
        }

        // Group data berdasarkan drying_time
        $grouped = collect($mappedData)->groupBy('drying_time');

        // Simpan ke database
        DB::transaction(function () use ($grouped) {
            foreach ($grouped as $drying_time => $measurements) {
                $group = TrainingGroup::create([
                    'drying_time' => $drying_time,
                    'process_id'  => null, // tambahkan jika perlu
                ]);

                foreach ($measurements as $row) {
                    TrainingData::create([
                        'training_group_id'     => $group->id,
                        'grain_temperature'     => $row['grain_temperature'],
                        'grain_moisture'        => $row['grain_moisture'],
                        'room_temperature'      => $row['room_temperature'],
                        'combustion_temperature'=> $row['combustion_temperature'],
                        'weight'                => $row['weight'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Import berhasil']);
    }
}
