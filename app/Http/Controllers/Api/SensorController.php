<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SensorController extends Controller
{
    public function getByDevice(Request $request)
    {
        $deviceId = $request->query('device_id');

        if ($deviceId) {
            // Ambil semua data sensor untuk perangkat tertentu
            $query = SensorData::with(['dryingProcess', 'sensorDevice'])
                ->where('device_id', $deviceId)
                ->orderBy('timestamp', 'desc');

            $sensorData = $query->get();

            // Hitung rata-rata untuk perangkat tertentu
            $averages = SensorData::selectRaw('
                AVG(kadar_air_gabah) as avg_kadar_air_gabah,
                AVG(suhu_gabah) as avg_suhu_gabah,
                AVG(suhu_ruangan) as avg_suhu_ruangan
            ')
                ->where('device_id', $deviceId)
                ->first();
        } else {
            // Ambil data sensor terakhir untuk setiap perangkat
            $sensorData = SensorData::with(['dryingProcess', 'sensorDevice'])
                ->select('sensor_data.*')
                ->whereIn('sensor_data.sensor_id', function ($query) {
                    $query->selectRaw('MAX(sensor_id)')
                        ->from('sensor_data')
                        ->groupBy('device_id');
                })
                ->orderBy('timestamp', 'desc')
                ->get();

            // Hitung rata-rata dari data sensor terakhir
            $averages = SensorData::selectRaw('
                AVG(kadar_air_gabah) as avg_kadar_air_gabah,
                AVG(suhu_gabah) as avg_suhu_gabah,
                AVG(suhu_ruangan) as avg_suhu_ruangan
            ')
                ->whereIn('sensor_id', function ($query) {
                    $query->selectRaw('MAX(sensor_id)')
                        ->from('sensor_data')
                        ->groupBy('device_id');
                })
                ->first();
        }

        return response()->json([
            'message' => 'Data sensor berhasil diambil',
            'data' => $sensorData->map(function ($item) {
                return [
                    'proses_pengeringan' => $item->dryingProcess->name ?? '-',
                    // 'timestamp' => $item->timestamp,
                    'timestamp' => \Carbon\Carbon::parse($item->timestamp)
                        ->timezone('Asia/Jakarta')
                        ->format('Y-m-d H:i:s'),

                    'kadar_air_gabah' => $item->kadar_air_gabah ?? '-',
                    'suhu_gabah' => $item->suhu_gabah ?? '-',
                    'suhu_ruangan' => $item->suhu_ruangan ?? '-',
                ];
            }),
            'averages' => [
                'avg_kadar_air_gabah' => round($averages->avg_kadar_air_gabah, 2) ?? 0,
                'avg_suhu_gabah' => round($averages->avg_suhu_gabah, 2) ?? 0,
                'avg_suhu_ruangan' => round($averages->avg_suhu_ruangan, 2) ?? 0,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:sensor_devices,device_id',
            'device_type' => 'required|in:grain_sensor,room_sensor',
            'kadar_air_gabah' => 'nullable|numeric|required_if:device_type,grain_sensor',
            'suhu_gabah' => 'nullable|numeric|required_if:device_type,grain_sensor',
            'suhu_ruangan' => 'nullable|numeric|required_if:device_type,room_sensor',
            'timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'device_id' => $request->device_id,
            'timestamp' => $request->timestamp,
        ];

        if ($request->device_type === 'grain_sensor') {
            $data['kadar_air_gabah'] = $request->kadar_air_gabah;
            $data['suhu_gabah'] = $request->suhu_gabah;
        } else {
            $data['suhu_ruangan'] = $request->suhu_ruangan;
        }

        $sensorData = SensorData::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Data sensor berhasil ditambahkan',
            'data' => $sensorData
        ], 201);
    }
}
