<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use App\Models\SensorDevice;
use App\Models\BedDryer;
use App\Services\MqttOneShotPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SensorDevicesController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Pastikan user login
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $userId  = $user->id ?? $user->user_id;
            $dryerId = $request->query('dryer_id');

            // Jika dryer_id dikirim, pastikan dryer tsb milik user
            if (!empty($dryerId)) {
                $ownsDryer = BedDryer::where('dryer_id', $dryerId)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$ownsDryer) {
                    // Dryer bukan milik user → kembalikan kosong namun 200
                    return response()->json([
                        'status' => 'success',
                        'data'   => [],
                    ], 200);
                }
            }

            // Ambil hanya devices yang terkait bed_dryers milik user.
            // Asumsi: sensor_devices punya kolom dryer_id yang merujuk ke bed_dryers.dryer_id
            $query = SensorDevice::query()
                ->join('bed_dryers', 'bed_dryers.dryer_id', '=', 'sensor_devices.dryer_id')
                ->where('bed_dryers.user_id', $userId)
                ->when(!empty($dryerId), fn ($q) => $q->where('sensor_devices.dryer_id', $dryerId))
                ->select('sensor_devices.*')
                ->orderBy('sensor_devices.device_name', 'asc');

            $devices = $query->get();

            Carbon::setLocale('id');

            $formattedDevices = $devices->map(function ($device) {
                return [
                    'device_id'  => $device->device_id,
                    'device_name'=> $device->device_name,
                    'address'    => $device->address,
                    'location'    => $device->location,
                    'status'     => (bool) $device->status,
                    // format tanggal berbahasa Indonesia
                    'created_at' => $device->created_at
                        ? Carbon::parse($device->created_at)->locale('id')->isoFormat('D MMMM Y')
                        : null,
                    'updated_at' => $device->updated_at
                        ? Carbon::parse($device->updated_at)->locale('id')->isoFormat('D MMMM Y')
                        : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $formattedDevices,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data perangkat: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resetAndDelete(Request $request, SensorDevice $device)
    {
        try {
            // Kolom address berisi topik telemetry, contoh: iot/mitra1/dryer1/1
            $telemetry = (string) $device->address;

            if ($telemetry === '') {
                return response()->json(['status' => 'error', 'message' => 'Telemetry topic kosong.'], 400);
            }

            // Bentuk reset topic: ganti "/{panelId}" dengan "/resetwifi/{panelId}"
            // Contoh: iot/mitra1/dryer1/1 -> iot/mitra1/dryer1/resetwifi/1
            $resetTopic = preg_replace('#/(\d+)$#', '/resetwifi/$1', $telemetry);
            if (!$resetTopic || $resetTopic === $telemetry) {
                return response()->json(['status' => 'error', 'message' => 'Gagal mengkonstruksi reset topic.'], 400);
            }

            // Publish "reset" (tidak retained)
            $ok = MqttOneShotPublisher::publish($resetTopic, 'reset', 0, false);
            if (!$ok) {
                return response()->json(['status' => 'error', 'message' => 'Gagal mengirim perintah reset via MQTT.'], 500);
            }

            // Hapus device dari DB
            $id = $device->device_id;
            $device->delete();

            Log::info('Device reset & deleted', ['device_id' => $id, 'reset_topic' => $resetTopic]);

            return response()->json([
                'status' => 'success',
                'message' => 'Perangkat direset & dihapus.',
                'device_id' => $id,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('resetAndDelete failed: ' . $e->getMessage(), ['device_id' => $device->device_id ?? null]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }
}
