<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DryingProcess;
use App\Models\BedDryer;
use App\Models\SensorDevice;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function adminSummary()
    {
        try {
            // 1. Total mitra (user dengan id > 1)
            $totalMitra = User::where('id', '>', 1)->count();

            // 2. Proses pengeringan yang sedang berlangsung (status ongoing)
            $ongoingProcesses = DryingProcess::where('status', 'ongoing')
                ->join('bed_dryers', 'drying_process.dryer_id', '=', 'bed_dryers.dryer_id')
                ->join('users', 'bed_dryers.user_id', '=', 'users.id')
                ->select(
                    'drying_process.process_id',
                    'bed_dryers.user_id',
                    'drying_process.status',
                    'users.name'
                )
                ->get();

            // Ambil daftar mitra unik yang sedang punya proses berjalan
            $mitraOngoing = $ongoingProcesses
                ->pluck('name')
                ->unique()
                ->values()
                ->all();

            // 3. Total alat terpasang (dari tabel sensor_devices)
            $totalAlat = SensorDevice::count();

            // 4. Detail alat + nama mitra
            $alatDetail = SensorDevice::join('bed_dryers', 'sensor_devices.dryer_id', '=', 'bed_dryers.dryer_id')
                ->join('users', 'bed_dryers.user_id', '=', 'users.id')
                ->select('sensor_devices.device_name', 'users.name as mitra')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_mitra' => $totalMitra,
                    'mitra_ongoing' => $mitraOngoing,
                    'total_alat' => $totalAlat,
                    'alat_detail' => $alatDetail
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Admin summary error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}