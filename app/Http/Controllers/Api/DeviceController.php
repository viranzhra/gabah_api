<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorDevice;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    // Menampilkan daftar perangkat
    public function index()
    {
        $data = SensorDevice::select('device_id', 'device_name', 'location', 'device_type')->get();

        // Tambah index (penomoran)
        $data = $data->map(function ($item, $key) {
            $item->index = $key + 1;
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    // Menyimpan perangkat baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string|max:50',
            'location' => 'required|string|max:100',
            'device_type' => 'required|in:grain_sensor,room_sensor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $device = SensorDevice::create([
            'device_name' => $request->device_name,
            'location' => $request->location,
            'device_type' => $request->device_type,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Perangkat berhasil ditambahkan',
            'data' => $device
        ], 201);
    }

    // Mengedit perangkat
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string|max:50',
            'location' => 'required|string|max:100',
            'device_type' => 'required|in:grain_sensor,room_sensor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $device = SensorDevice::findOrFail($id);
        $device->update([
            'device_name' => $request->device_name,
            'location' => $request->location,
            'device_type' => $request->device_type,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Perangkat berhasil diupdate',
            'data' => $device
        ]);
    }

    // Menghapus perangkat
    public function destroy($id)
    {
        $device = SensorDevice::findOrFail($id);
        $device->delete();

        return response()->json([
            'status' => true,
            'message' => 'Perangkat berhasil dihapus'
        ]);
    }

    // Menghitung rata-rata data sensor
    public function averages()
    {
        $averages = SensorData::selectRaw('
            AVG(kadar_air_gabah) as avg_kadar_air_gabah,
            AVG(suhu_gabah) as avg_suhu_gabah,
            AVG(suhu_ruangan) as avg_suhu_ruangan
        ')->first();

        return response()->json([
            'status' => true,
            'data' => [
                'avg_kadar_air_gabah' => round($averages->avg_kadar_air_gabah, 2) ?? 0,
                'avg_suhu_gabah' => round($averages->avg_suhu_gabah, 2) ?? 0,
                'avg_suhu_ruangan' => round($averages->avg_suhu_ruangan, 2) ?? 0,
            ]
        ]);
    }
}