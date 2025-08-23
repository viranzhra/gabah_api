<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DryingProcess;
use App\Models\SensorDevice;

class DashboardController extends Controller
{
    /**
     * Endpoint ringkasan dashboard untuk Administrator
     */
    public function adminSummary()
    {
        try {
            // 1. Total mitra (user dengan id > 1)
            $totalMitra = User::where('id', '>', 1)->count();

            // 2. Proses pengeringan yang sedang berlangsung (status ongoing)
            $ongoingProcesses = DryingProcess::with('user:id,name')
                ->where('status', 'ongoing')
                ->get(['process_id', 'user_id', 'lokasi', 'status']);

            // Ambil daftar mitra unik yang sedang punya proses berjalan
            $mitraOngoing = $ongoingProcesses
                ->pluck('user.name')
                ->unique()
                ->values()
                ->all();

            // 3. Total alat terpasang (dari tabel sensor_devices)
            $totalAlat = SensorDevice::count();

            // Detail alat + nama mitra
            $alatDetail = SensorDevice::join('bed_dryers', 'sensor_devices.dryer_id', '=', 'bed_dryers.dryer_id')
                ->join('users', 'bed_dryers.user_id', '=', 'users.id')
                ->get([
                    'sensor_devices.device_name',
                    'users.name as mitra'
                ]);

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
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
