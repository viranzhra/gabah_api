<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\BedDryer;
use App\Models\SensorData;
use App\Models\PredictionEstimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Notifier;

class MobilePredictionController extends Controller
{
    public function startPrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dryer_id'         => 'required|integer|exists:bed_dryers,dryer_id',
            'grain_type_id'    => 'required|integer|exists:grain_types,grain_type_id',
            'berat_gabah_awal' => 'required|numeric|min:100',
            'kadar_air_target' => 'required|numeric|min:10|max:20',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for start prediction', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $user = $request->user();
            // Log::info('Received berat_gabah_awal', ['berat' => $request->berat_gabah_awal]);

            $dryer = BedDryer::where('dryer_id', $request->dryer_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Cari proses pending/ongoing pada dryer ini; kalau tidak ada buat pending
            $dryingProcess = DryingProcess::where('dryer_id', $dryer->dryer_id)
                ->whereIn('status', ['pending'])
                ->first();

            if ($dryingProcess) {
                $dryingProcess->update([
                    'grain_type_id'      => (int) $request->grain_type_id,
                    'berat_gabah_awal'   => (float) $request->berat_gabah_awal,
                    'kadar_air_target'   => (float) $request->kadar_air_target,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'durasi_rekomendasi' => 0,
                ]);
            } else {
                $dryingProcess = DryingProcess::create([
                    'dryer_id'           => $dryer->dryer_id,
                    'grain_type_id'      => (int) $request->grain_type_id,
                    'berat_gabah_awal'   => (float) $request->berat_gabah_awal,
                    'kadar_air_target'   => (float) $request->kadar_air_target,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'durasi_rekomendasi' => 0,
                ]);
            }

            return response()->json([
                'message'    => 'Prediction started successfully, waiting for sensor data...',
                'process_id' => $dryingProcess->process_id,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error starting prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to start prediction'], 500);
        }
    }

    public function stopPrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|integer|exists:drying_process,process_id'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for stop prediction', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $dryingProcess = DryingProcess::where('process_id', $request->process_id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->first();

            if (!$dryingProcess) {
                return response()->json(['message' => 'No active process to stop'], 200);
            }

            $latestSensorData = SensorData::where('process_id', $dryingProcess->process_id)->latest()->first();

            $kadar_air_akhir   = $latestSensorData?->kadar_air_gabah;
            $durasi_terlaksana = $dryingProcess->timestamp_mulai
                ? round(Carbon::parse($dryingProcess->timestamp_mulai)->diffInMinutes(now()))
                : 0;

            $avg_estimasi_durasi = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                ->where('estimasi_durasi', '>', 0)
                ->avg('estimasi_durasi');

            $dryingProcess->update([
                'status'              => 'completed',
                'kadar_air_akhir'     => $kadar_air_akhir,
                'durasi_terlaksana'   => $durasi_terlaksana,
                'avg_estimasi_durasi' => $avg_estimasi_durasi,
                'timestamp_selesai'   => now(),
            ]);

            return response()->json([
                'message'    => 'Prediction stopped successfully',
                'process_id' => $dryingProcess->process_id,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error stopping prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to stop prediction'], 500);
        }
    }

public function receivePrediction(Request $request)
{
    $validator = Validator::make($request->all(), [
        'process_id'            => 'required|integer|exists:drying_process,process_id',
        'grain_type_id'         => 'required|integer|exists:grain_types,grain_type_id',
        'kadar_air_gabah'       => 'required|numeric|min:0',
        'predicted_drying_time' => 'required|numeric|min:0',
        'timestamp'             => 'required|numeric'
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed for prediction data', ['errors' => $validator->errors()]);
        return response()->json(['error' => $validator->errors()], 400);
    }

    try {
        // TIDAK pakai $request->user() karena endpoint ini di luar sanctum
        $dryingProcess = DryingProcess::where('process_id', $request->process_id)
            ->whereIn('status', ['pending', 'ongoing'])
            ->firstOrFail();

        // Ambil dryer & tentukan TARGET USER dari owner dryer
        $dryer = BedDryer::where('dryer_id', $dryingProcess->dryer_id)->first();
        $targetUserId = $dryer?->user_id; // <- penting: ini yang akan menerima notifikasi
        if (!$targetUserId) {
            // Kalau tidak ada owner, kita tetap lanjutkan simpan estimasi, tapi tidak bisa buat notifikasi ke user tertentu
            Log::warning('receivePrediction: dryer has no owner user_id; skip notifications', [
                'dryer_id' => $dryingProcess->dryer_id,
                'process_id' => $dryingProcess->process_id,
            ]);
        }

        // Pastikan data proses lengkap
        if (is_null($dryingProcess->grain_type_id) || is_null($dryingProcess->berat_gabah_awal) || is_null($dryingProcess->kadar_air_target)) {
            return response()->json(['message' => 'Incomplete drying process data, prediction not stored'], 200);
        }

        // Set status & timestamp mulai
        if ($dryingProcess->status === 'pending') {
            $dryingProcess->update(['status' => 'ongoing']);
        }
        if (is_null($dryingProcess->timestamp_mulai)) {
            $dryingProcess->update(['timestamp_mulai' => now()]);
        }

        // Update rekomendasi durasi (bulatkan ke menit)
        // if (is_null($dryingProcess->durasi_rekomendasi) || $dryingProcess->durasi_rekomendasi == 0) {
            $dryingProcess->update(['durasi_rekomendasi' => round($request->predicted_drying_time)]);
        // }

        // Simpan titik estimasi
        PredictionEstimation::create([
            'process_id'      => $dryingProcess->process_id,
            'estimasi_durasi' => $request->predicted_drying_time,
            'timestamp'       => date('Y-m-d H:i:s', $request->timestamp),
        ]);

        // Set kadar_air_awal sekali (ambil dari SensorData paling awal yang ada MC)
        if (is_null($dryingProcess->kadar_air_awal)) {
            $firstSensorData = SensorData::where('process_id', $dryingProcess->process_id)
                ->whereNotNull('kadar_air_gabah')
                ->orderBy('timestamp', 'asc')
                ->first();

            if ($firstSensorData) {
                $dryingProcess->update(['kadar_air_awal' => $firstSensorData->kadar_air_gabah]);
            }
        }

        // Update durasi terlaksana
        $durasiTerlaksana = $dryingProcess->timestamp_mulai
            ? round(Carbon::parse($dryingProcess->timestamp_mulai)->diffInMinutes(now()))
            : 0;
        $dryingProcess->update(['durasi_terlaksana' => $durasiTerlaksana]);

        // Jika MC saat ini sudah <= target → close proses
        if ($request->kadar_air_gabah <= $dryingProcess->kadar_air_target) {
            $avg_estimasi_durasi = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                ->where('estimasi_durasi', '>', 0)
                ->avg('estimasi_durasi');

            $dryingProcess->update([
                'status'              => 'completed',
                'kadar_air_akhir'     => $request->kadar_air_gabah,
                'durasi_terlaksana'   => $durasiTerlaksana,
                'avg_estimasi_durasi' => $avg_estimasi_durasi,
                'timestamp_selesai'   => now(),
            ]);
        }

        // ================== LOGIKA NOTIFIKASI (INLINE, TANPA HELPER) ==================

        // NOTE: Flutter menarik dari tabel `notifications` (menurut migrasi Anda: create_notifications_table)
        // Struktur kolom asumsi: id, user_id, dryer_id, process_id, type, title, body, data(json), created_at, updated_at

        // 1) "RATA-RATA" KADAR AIR -> gunakan langsung nilai request
        if ($targetUserId) {
            $avgMc  = (float) $request->kadar_air_gabah;
            $target = (float) ($dryingProcess->kadar_air_target ?? 14.0);

            if ($avgMc <= $target && !$dryingProcess->notif_target_sent) {
                DB::table('app_notifications')->insert([
                    'user_id'    => $targetUserId,
                    'dryer_id'   => $dryingProcess->dryer_id,
                    'process_id' => $dryingProcess->process_id,
                    'type'       => 'target_moisture_reached',
                    'title'      => 'Target Kadar Air Tercapai',
                    'body'       => sprintf('Rata-rata kadar air gabah telah mencapai %.2f%% (target ≤ %.2f%%).', $avgMc, $target),
                    'data'       => json_encode([
                        'avg_mc'    => round($avgMc, 2),
                        'target_mc' => $target,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $dryingProcess->update(['notif_target_sent' => true]);
            }

            // 2) SISA DURASI (menit)
            $rekom = (int)($dryingProcess->durasi_rekomendasi ?? 0);
            $sisa  = max(0, $rekom - (int)$durasiTerlaksana);

            if ($sisa <= 15 && !$dryingProcess->notif_15m_sent) {
                DB::table('app_notifications')->insert([
                    'user_id'    => $targetUserId,
                    'dryer_id'   => $dryingProcess->dryer_id,
                    'process_id' => $dryingProcess->process_id,
                    'type'       => 'eta_15',
                    'title'      => 'Pengeringan: Sisa ±15 Menit',
                    'body'       => 'Perkiraan pengeringan akan selesai sekitar 15 menit lagi.',
                    'data'       => json_encode(['sisa_menit' => $sisa]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $dryingProcess->update(['notif_15m_sent' => true]);
            }

            if ($sisa <= 5 && !$dryingProcess->notif_5m_sent) {
                DB::table('app_notifications')->insert([
                    'user_id'    => $targetUserId,
                    'dryer_id'   => $dryingProcess->dryer_id,
                    'process_id' => $dryingProcess->process_id,
                    'type'       => 'eta_5',
                    'title'      => 'Pengeringan: Sisa ±5 Menit',
                    'body'       => 'Perkiraan pengeringan tinggal 5 menit lagi.',
                    'data'       => json_encode(['sisa_menit' => $sisa]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $dryingProcess->update(['notif_5m_sent' => true]);
            }
        }

        // ==============================================================================

        return response()->json([
            'message'            => 'Prediction data received and stored successfully',
            'process_id'         => $dryingProcess->process_id,
            'estimated_duration' => $dryingProcess->durasi_rekomendasi,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error receiving prediction: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to receive prediction data'], 500);
    }
}

}
