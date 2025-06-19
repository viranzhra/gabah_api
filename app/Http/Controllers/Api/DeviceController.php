<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorDevice;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function index()
    {
        try {
            $data = SensorDevice::select('device_id', 'device_name', 'location', 'device_type')->get();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data alat sensor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
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
                'message' => 'Alat sensor berhasil ditambahkan',
                'data' => $device
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan alat sensor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $device = SensorDevice::select('device_id', 'device_name', 'location', 'device_type')->findOrFail($id);
            return response()->json([
                'status' => true,
                'data' => $device
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Alat sensor tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data alat sensor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
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
                'message' => 'Alat sensor berhasil diupdate',
                'data' => $device
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Alat sensor tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate alat sensor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $device = SensorDevice::findOrFail($id);
            $device->delete();

            return response()->json([
                'status' => true,
                'message' => 'Alat sensor berhasil dihapus'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Alat sensor tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus alat sensor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function averages()
    {
        try {
            // Check if the SensorData table exists and has data
            if (!\Schema::hasTable('sensor_data')) {
                throw new \Exception('Tabel sensor_data tidak ditemukan.');
            }

            $averages = SensorData::selectRaw('
                AVG(kadar_air_gabah) as avg_kadar_air_gabah,
                AVG(suhu_gabah) as avg_suhu_gabah,
                AVG(suhu_ruangan) as avg_suhu_ruangan
            ')->first();

            return response()->json([
                'status' => true,
                'data' => [
                    'avg_kadar_air_gabah' => round($averages->avg_kadar_air_gabah ?? 0, 2),
                    'avg_suhu_gabah' => round($averages->avg_suhu_gabah ?? 0, 2),
                    'avg_suhu_ruangan' => round($averages->avg_suhu_ruangan ?? 0, 2),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data rata-rata: ' . $e->getMessage()
            ], 500);
        }
    }
}