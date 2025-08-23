<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SSEController extends Controller
{
    public function stream($processId)
    {
        return response()->stream(function () use ($processId) {
            static $lastPeriodicUpdate = 0;
            static $lastNearTargetNotification = 0;

            while (true) {
                Log::info("SSE stream started for process_id: {$processId}");
                try {
                    $proses = DB::table('drying_process')
                        ->where('process_id', $processId)
                        ->first();

                    if (!$proses) {
                        Log::error("Process ID {$processId} not found");
                        echo "event: sensor-update\n";
                        echo "data: " . json_encode([
                            'error' => 'Process ID tidak ditemukan.',
                            'status' => 'invalid'
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                        break;
                    }

                    if ($proses->status !== 'ongoing') {
                        Log::error("Process status is not ongoing: {$proses->status}");
                        echo "event: sensor-update\n";
                        echo "data: " . json_encode([
                            'error' => "Status proses saat ini: {$proses->status}",
                            'status' => 'invalid'
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                        break;
                    }

                    $sensorAverages = DB::table('sensor_data')
                        ->where('process_id', $processId)
                        ->select(
                            DB::raw('ROUND(AVG(kadar_air_gabah)::numeric, 2) as kadar_air'),
                            DB::raw('ROUND(AVG(suhu_gabah)::numeric, 2) as suhu_gabah'),
                            DB::raw('ROUND(AVG(suhu_ruangan)::numeric, 2) as suhu_ruangan'),
                            DB::raw('ROUND(AVG(suhu_pembakaran)::numeric, 2) as suhu_pembakaran')
                        )
                        ->first();

                    $latestSensor = DB::table('sensor_data')
                        ->where('process_id', $processId)
                        ->orderBy('timestamp', 'desc')
                        ->first();

                    $estimasi = DB::table('prediction_estimations')
                        ->where('process_id', $processId)
                        ->orderBy('timestamp', 'desc')
                        ->first();

                    if ($sensorAverages && $latestSensor) {
                        $estimasiDurasi = null;
                        $durasiRekomendasi = null;
                        $durasiTerlaksana = 0;

                        if ($proses->timestamp_mulai && Carbon::parse($proses->timestamp_mulai)->lessThanOrEqualTo(Carbon::now())) {
                            $startTime = Carbon::parse($proses->timestamp_mulai);
                            $durasiTerlaksana = round($startTime->diffInMinutes(Carbon::now()), 2);
                        } else {
                            Log::warning("Invalid or future timestamp_mulai for process_id: {$processId}, timestamp_mulai: " . ($proses->timestamp_mulai ?? 'null'));
                        }

                        if ($estimasi) {
                            $estimasiDurasi = round($estimasi->estimasi_durasi, 2);
                        }
                        if ($proses->durasi_rekomendasi) {
                            $durasiRekomendasi = round($proses->durasi_rekomendasi, 2);
                        }

                        Log::info("Sending SSE data for process_id: {$processId}", [
                            'kadar_air' => $sensorAverages->kadar_air,
                            'suhu_gabah' => $sensorAverages->suhu_gabah,
                            'suhu_ruangan' => $sensorAverages->suhu_ruangan,
                            'suhu_pembakaran' => $sensorAverages->suhu_pembakaran,
                            'durasi_terlaksana' => $durasiTerlaksana,
                            'estimasi_durasi' => $estimasiDurasi,
                            'durasi_rekomendasi' => $durasiRekomendasi
                        ]);

                        // Notifikasi: Proses pengeringan selesai
                        if ($sensorAverages->kadar_air <= $proses->kadar_air_target) {
                            $notificationData = [
                                'message' => 'Proses pengeringan selesai! Kadar air telah mencapai target ' . $proses->kadar_air_target . '%.',
                                'type' => 'success',
                                'process_id' => $processId,
                                'notification_id' => 'process_completed_' . $processId
                            ];
                            echo "event: notification\n";
                            echo "data: " . json_encode($notificationData) . "\n\n";
                        }

                        // Notifikasi: Suhu pembakaran terlalu tinggi
                        if ($sensorAverages->suhu_pembakaran > 350) {
                            $notificationData = [
                                'message' => 'Peringatan: Suhu pembakaran terlalu tinggi (' . $sensorAverages->suhu_pembakaran . '°C)!',
                                'type' => 'warning',
                                'process_id' => $processId,
                                'notification_id' => 'high_temperature_' . $processId . '_' . time()
                            ];
                            echo "event: notification\n";
                            echo "data: " . json_encode($notificationData) . "\n\n";
                        }

                        // Notifikasi: Proses telah berjalan sekian menit (setiap 10 menit)
                        // if (time() - $lastPeriodicUpdate >= 600) {
                        //     $lastPeriodicUpdate = time();
                        //     $notificationData = [
                        //         'message' => 'Proses pengeringan telah berjalan selama ' . $durasiTerlaksana . ' menit.',
                        //         'type' => 'info',
                        //         'process_id' => $processId,
                        //         'notification_id' => 'duration_update_' . $processId . '_' . $lastPeriodicUpdate
                        //     ];
                        //     echo "event: notification\n";
                        //     echo "data: " . json_encode($notificationData) . "\n\n";
                        // }

                        // Notifikasi: Kadar air mendekati target (dalam 5% dari target)
                        if ($proses->kadar_air_target && $sensorAverages->kadar_air <= ($proses->kadar_air_target * 1.05) && $sensorAverages->kadar_air > $proses->kadar_air_target && time() - $lastNearTargetNotification >= 300) {
                            $lastNearTargetNotification = time();
                            $notificationData = [
                                'message' => 'Kadar air rata-rata (' . $sensorAverages->kadar_air . '%) mendekati target (' . $proses->kadar_air_target . '%).',
                                'type' => 'info',
                                'process_id' => $processId,
                                'notification_id' => 'near_target_' . $processId . '_' . $lastNearTargetNotification
                            ];
                            echo "event: notification\n";
                            echo "data: " . json_encode($notificationData) . "\n\n";
                        }

                        // Data sensor reguler
                        echo "event: sensor-update\n";
                        echo "data: " . json_encode([
                            'kadar_air' => $sensorAverages->kadar_air,
                            'suhu_gabah' => $sensorAverages->suhu_gabah,
                            'suhu_ruangan' => $sensorAverages->suhu_ruangan,
                            'suhu_pembakaran' => $sensorAverages->suhu_pembakaran,
                            'durasi_terlaksana' => $durasiTerlaksana,
                            'estimasi_durasi' => $estimasiDurasi,
                            'durasi_rekomendasi' => $durasiRekomendasi,
                            'status' => $sensorAverages->kadar_air <= $proses->kadar_air_target ? 'selesai' : 'berlangsung',
                            'timestamp' => $latestSensor->timestamp
                        ]) . "\n\n";
                    } else {
                        Log::warning("No sensor data or latest sensor for process_id: {$processId}");
                        echo "event: sensor-update\n";
                        echo "data: " . json_encode([
                            'error' => 'Tidak ada data sensor atau data terbaru untuk process_id ini.',
                            'status' => 'invalid'
                        ]) . "\n\n";
                    }

                    ob_flush();
                    flush();
                    sleep(2);
                } catch (\Exception $e) {
                    Log::error("SSE error for process_id: {$processId}: " . $e->getMessage());
                    echo "event: error\n";
                    echo "data: " . json_encode(['error' => 'Server error: ' . $e->getMessage()]) . "\n\n";
                    ob_flush();
                    flush();
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    // public function getOngoingProcess()
    // {
    //     try {
    //         $process = DB::table('drying_process')
    //             ->where('status', 'ongoing')
    //             ->orderBy('timestamp_mulai', 'desc')
    //             ->first();

    //         if ($process) {
    //             return response()->json([
    //                 'success' => true,
    //                 'process_id' => $process->process_id
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'error' => 'Tidak ada proses dengan status sedang berlangsung.'
    //             ], 404);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error("Error fetching ongoing process: " . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'error' => 'Server error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getOngoingProcess(Request $request)
{
    try {
        $userId  = Auth::id();
        $dryerId = $request->query('dryer_id'); // opsional

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error'   => 'User tidak terautentikasi.'
            ], 401);
        }

        $process = DB::table('drying_process as p')
            ->leftJoin('bed_dryers as d', 'd.dryer_id', '=', 'p.dryer_id')
            ->leftJoin('grain_types as g', 'g.grain_type_id', '=', 'p.grain_type_id')
            ->where('p.status', 'ongoing')
            ->where(function($q) use ($userId) {
                $q->where('d.user_id', $userId)
                  ->orWhere('p.user_id', $userId);
            })
            ->when($dryerId, fn($q) => $q->where('p.dryer_id', $dryerId))
            ->orderByDesc('p.timestamp_mulai')
            ->select([
                'p.process_id',
                'p.dryer_id',
                'p.timestamp_mulai',
                'p.timestamp_selesai',
                'p.kadar_air_target',
                'p.kadar_air_awal',
                'p.kadar_air_akhir',
                'p.status',
                'd.nama as dryer_name',
                'g.nama_jenis as grain_type',
            ])
            ->first();

        if (!$process) {
            return response()->json([
                'success' => false,
                'error'   => 'Tidak ada proses pengeringan berlangsung untuk saat ini.'
            ], 200); // ubah jadi 200 supaya di JS tidak dianggap error
        }

        $latestSensor = DB::table('sensor_data as sd')
            ->join('sensor_devices as sdev', 'sd.device_id', '=', 'sdev.device_id')
            ->join('bed_dryers as bd', 'sdev.dryer_id', '=', 'bd.dryer_id')
            ->where('sd.process_id', $process->process_id)
            ->where('bd.user_id', $userId)
            ->orderByDesc('sd.created_at')
            ->select(
                'sd.suhu_pembakaran',
                'sd.suhu_gabah',
                'sd.suhu_ruangan',
                'sd.kadar_air_gabah',
                'sd.created_at',
                'bd.nama as dryer_name'
            )
            ->first();

        return response()->json([
            'success'          => true,
            'message'          => "Sedang ada proses pengeringan berlangsung",
            'process_id'       => (int) $process->process_id,
            'dryer_id'         => (int) $process->dryer_id,
            'dryer_name'       => $process->dryer_name,
            'started_at'       => $process->timestamp_mulai,
            'finished_at'      => $process->timestamp_selesai,
            'grain_type'       => $process->grain_type,
            'kadar_air_target' => $process->kadar_air_target,
            'kadar_air_awal'   => $process->kadar_air_awal,
            'kadar_air_akhir'  => $process->kadar_air_akhir,
            'status'           => $process->status,
            'latest_sensor'    => $latestSensor ? [
                'suhu_pembakaran' => $latestSensor->suhu_pembakaran,
                'suhu_gabah'      => $latestSensor->suhu_gabah,
                'suhu_ruangan'    => $latestSensor->suhu_ruangan,
                'kadar_air_gabah' => $latestSensor->kadar_air_gabah,
                'timestamp'       => $latestSensor->created_at,
                'dryer_name'      => $latestSensor->dryer_name,
            ] : null,
        ]);
    } catch (\Throwable $e) {
        Log::error("Error fetching ongoing process: ".$e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'error'   => 'Server error: '.$e->getMessage()
        ], 500);
    }
}


    // Alias lama /ongoing-process → tetap bekerja
    public function ongoing(Request $request)
    {
        return $this->getOngoingProcess($request);
    }
}