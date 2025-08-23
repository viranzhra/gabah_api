<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryerProcess;
use App\Models\SensorData;
use App\Models\User;
use App\Models\PredictionEstimation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class DryerProcessController extends Controller
{
    public function index(Request $request)
{
    try {
        $userId = $request->user()->id;

        $process = DryerProcess::query()
            ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
            ->select(
                'drying_process.process_id',
                'drying_process.user_id',
                'drying_process.grain_type_id',
                'drying_process.kadar_air_target',
                'drying_process.berat_gabah_awal',
                'drying_process.berat_gabah_akhir',
                'drying_process.kadar_air_awal',
                'drying_process.kadar_air_akhir',
                'drying_process.avg_estimasi_durasi',
                'drying_process.durasi_rekomendasi',
                'drying_process.durasi_terlaksana',
                'drying_process.status',
                'drying_process.timestamp_mulai',
                'drying_process.timestamp_selesai',
                'drying_process.catatan',
                'grain_types.nama_jenis'
            )
            ->where('drying_process.user_id', $userId)
            ->latest('drying_process.process_id')
            ->first();

        if ($process) {
            $targetKadarAir = !is_null($process->kadar_air_target)
                ? number_format($process->kadar_air_target, 2) . '%'
                : 'Tidak tersedia';

            $sensorData = SensorData::where('process_id', $process->process_id)
                ->latest('timestamp')
                ->first();

            $kadarAirSaatIni = ($sensorData && !is_null($sensorData->kadar_air))
                ? number_format($sensorData->kadar_air, 2) . '%'
                : 'Tidak tersedia';

            $process->timestamp_mulai = $process->timestamp_mulai;
            $process->timestamp_selesai = $process->timestamp_selesai;

            // Kirim raw timestamp dalam format ISO untuk frontend
            // $process->timestamp_mulai = $process->timestamp_mulai
            //     ? Carbon::parse($process->timestamp_mulai)->timezone('Asia/Jakarta')->toIso8601String()
            //     : null;

            // $process->timestamp_selesai = $process->timestamp_selesai
            //     ? Carbon::parse($process->timestamp_selesai)->timezone('Asia/Jakarta')->toIso8601String()
            //     : null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'process' => $process,
                'targetKadarAir' => $targetKadarAir ?? 'Tidak tersedia',
                'kadarAirSaatIni' => $kadarAirSaatIni ?? 'Tidak tersedia'
            ]
        ], 200);

    } catch (QueryException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage(),
            'data' => [
                'process' => null,
                'targetKadarAir' => '14%',
                'kadarAirSaatIni' => 'Tidak tersedia'
            ]
        ], 500);
    }
}


    // public function startDryingProcess(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,id',
    //         'grain_type_id' => 'required|exists:grain_types,grain_type_id',
    //         'kadar_air_target' => 'required|numeric',
    //         'lokasi' => 'nullable|string|max:100',
    //         'berat_gabah_awal' => 'nullable|numeric',
    //         'kadar_air_awal' => 'nullable|numeric',
    //         'status' => 'required|in:pending,ongoing,completed',
    //         'timestamp_mulai' => 'required|date_format:Y-m-d H:i:s'
    //     ]);

    //     if ($validator->fails()) {
    //         Log::error("Validation failed for startDryingProcess: " . json_encode($validator->errors()));
    //         return response()->json(['error' => $validator->errors()], 422);
    //     }

    //     $process = DryerProcess::create([
    //         'user_id' => $request->user_id,
    //         'grain_type_id' => $request->grain_type_id,
    //         'kadar_air_target' => $request->kadar_air_target,
    //         'lokasi' => $request->lokasi,
    //         'berat_gabah_awal' => $request->berat_gabah_awal,
    //         'kadar_air_awal' => $request->kadar_air_awal,
    //         'status' => $request->status,
    //         'timestamp_mulai' => $request->timestamp_mulai,
    //         'durasi_rekomendasi' => 0
    //     ]);

    //     Log::info("Drying process started: " . json_encode($process->toArray()));
    //     return response()->json(['message' => 'Drying process started successfully', 'process_id' => $process->process_id], 201);
    // }
    public function startDryingProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grain_type_id' => 'required|exists:grain_types,grain_type_id',
            'kadar_air_target' => 'required|numeric',
            'lokasi' => 'nullable|string|max:100',
            'berat_gabah_awal' => 'nullable|numeric',
            'dryer_id' => 'nullable|numeric',
            'kadar_air_awal' => 'nullable|numeric',
            'durasi_rekomendasi' => 'nullable|numeric',
            'timestamp_mulai' => 'nullable|date_format:Y-m-d H:i:s',
            'user_id' => 'nullable|numeric',

        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for startDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user_id = $request->user_id;

        // Verify user exists
        if (!User::where('id', $user_id)->exists()) {
            Log::error("User with id {$user_id} does not exist");
            return response()->json(['error' => 'Invalid user_id: User does not exist'], 422);
        }

        try {
            // Cek apakah ada proses dengan status 'pending' untuk user yang sama
            $existingProcess = DryerProcess::where('status', 'pending')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($existingProcess) {
                // Update data proses yang pending
                $existingProcess->update([
                    'user_id' => $user_id,
                    'grain_type_id' => $request->grain_type_id,
                    'kadar_air_target' => $request->kadar_air_target,
                    'lokasi' => $request->lokasi ?? $existingProcess->lokasi,
                    'berat_gabah_awal' => $request->berat_gabah_awal,
                    'kadar_air_awal' => $request->kadar_air_awal,
                    'dryer_id' => $request->dryer_id,
                    'status' => 'ongoing',
                    'timestamp_mulai' => $request->timestamp_mulai ?? now()->format('Y-m-d H:i:s'),
                    'durasi_rekomendasi' => $request->durasi_rekomendasi ?? 0
                ]);

                Log::info("Pending drying process updated to ongoing: " . json_encode($existingProcess->toArray()));
                return response()->json([
                    'message' => 'Pending process updated successfully',
                    'process_id' => $existingProcess->process_id,
                    'user_id' => $existingProcess->user_id
                ], 200);
            }

            // Tidak ada pending, buat baru
            $process = DryerProcess::create([
                'user_id' => $user_id,
                'grain_type_id' => $request->grain_type_id,
                'kadar_air_target' => $request->kadar_air_target,
                'lokasi' => $request->lokasi,
                'berat_gabah_awal' => $request->berat_gabah_awal,
                'kadar_air_awal' => $request->kadar_air_awal,
                'dryer_id' => $request->dryer_id,
                'status' => 'ongoing',
                'timestamp_mulai' => $request->timestamp_mulai ?? now()->format('Y-m-d H:i:s'),
                'durasi_rekomendasi' => $request->durasi_rekomendasi ?? 0
            ]);

            Log::info("New drying process started: " . json_encode($process->toArray()));
            return response()->json([
                'message' => 'Drying process started successfully',
                'process_id' => $process->process_id,
                'user_id' => $process->user_id
            ], 201);
        } catch (QueryException $e) {
            Log::error("Database error in startDryingProcess: " . $e->getMessage());
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                return response()->json(['error' => 'Invalid user_id: User does not exist'], 422);
            }
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error in startDryingProcess: " . $e->getMessage());
            return response()->json(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    public function updateDryingProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|exists:drying_process,process_id',
            'avg_estimasi_durasi' => 'nullable|numeric',
            'timestamp_selesai' => 'nullable|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:pending,ongoing,completed',
            'kadar_air_akhir' => 'nullable|numeric',
            'durasi_aktual' => 'nullable|numeric',
            'durasi_rekomendasi' => 'nullable|numeric',
            'durasi_terlaksana' => 'nullable|numeric',
            'berat_gabah_akhir' => 'nullable|numeric',
            'catatan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for updateDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $process = DryerProcess::findOrFail($request->process_id);

        $fields = array_filter([
            'avg_estimasi_durasi' => $request->has('avg_estimasi_durasi') ? floatval($request->avg_estimasi_durasi) : null,
            'timestamp_selesai' => $request->timestamp_selesai,
            'status' => $request->status,
            'kadar_air_akhir' => $request->kadar_air_akhir,
            'durasi_aktual' => $request->durasi_aktual,
            'durasi_rekomendasi' => $request->durasi_rekomendasi,
            'durasi_terlaksana' => $request->durasi_terlaksana,
            'berat_gabah_akhir' => $request->berat_gabah_akhir,
            'catatan' => $request->catatan
        ]);

        Log::info("Received fields for update: " . json_encode($request->all()));
        Log::info("Fields to update drying process: " . json_encode($fields));
        Log::info("Fillable fields in model: " . json_encode($process->getFillable()));

        $process->update($fields);
        $updatedProcess = $process->fresh();

        Log::info("Drying process updated: " . json_encode($updatedProcess->toArray()));

        return response()->json(['message' => 'Drying process updated successfully', 'data' => $updatedProcess], 200);
    }

    public function getPredictionEstimations(Request $request, $process_id)
    {
        $validator = Validator::make(['process_id' => $process_id], [
            'process_id' => 'required|exists:drying_process,process_id'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for getPredictionEstimations: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $estimations = PredictionEstimation::where('process_id', $process_id)->get();
        return response()->json([
            'estimations' => $estimations->map(function ($estimation) {
                return [
                    'id' => $estimation->id,
                    'process_id' => $estimation->process_id,
                    'estimasi_durasi' => floatval($estimation->estimasi_durasi),
                    'timestamp' => $estimation->timestamp
                ];
            })->toArray()
        ], 200);
    }

    public function show($process_id)
    {
        $validator = Validator::make(['process_id' => $process_id], [
            'process_id' => 'required|exists:drying_process,process_id'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for getDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $process = DryerProcess::query()
            ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
            ->select(
                'drying_process.process_id',
                'drying_process.user_id',
                'drying_process.grain_type_id',
                'drying_process.kadar_air_target',
                'drying_process.lokasi',
                'drying_process.berat_gabah_awal',
                'drying_process.kadar_air_awal',
                'drying_process.status',
                'drying_process.timestamp_mulai',
                'grain_types.nama_jenis'
            )
            ->where('drying_process.process_id', $process_id)
            ->first();

        if (!$process) {
            Log::error("Drying process not found for process_id: {$process_id}");
            return response()->json(['error' => 'Drying process not found'], 404);
        }

        Log::info("Drying process retrieved: " . json_encode($process->toArray()));
        return response()->json(['data' => $process], 200);
    }

    public function riwayat(Request $request)
{
    $query = DryerProcess::query()
        ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
        ->select(
            'drying_process.process_id',
            'drying_process.user_id',
            'drying_process.grain_type_id',
            'drying_process.kadar_air_target',
            'drying_process.lokasi',
            'drying_process.berat_gabah_awal',
            'drying_process.berat_gabah_akhir',
            'drying_process.kadar_air_awal',
            'drying_process.kadar_air_akhir',
            'drying_process.durasi_rekomendasi',
            'drying_process.durasi_aktual',
            'drying_process.durasi_terlaksana',
            'drying_process.status',
            'drying_process.timestamp_mulai',
            'drying_process.timestamp_selesai',
            'drying_process.catatan',
            'grain_types.nama_jenis'
        )
        // 🔹 Filter berdasarkan user login
        ->where('drying_process.user_id', auth()->id());

    // Filter berdasarkan process_id jika ada
    if ($request->has('process_id')) {
        $query->where('drying_process.process_id', $request->input('process_id'));
    } else {
        // Hanya ambil proses dengan status completed jika tidak ada process_id
        $query->where('drying_process.status', 'completed');
    }

    // Filter berdasarkan tanggal jika ada
    if ($request->has('filter_tanggal')) {
        $tanggal = $request->input('filter_tanggal');
        $query->whereDate('drying_process.timestamp_mulai', $tanggal);
    }

    $data = $query->orderByDesc('drying_process.timestamp_mulai')->get();

    $formatted = $data->map(function ($item) {
        $timestampMulai = $item->timestamp_mulai 
            ? Carbon::parse($item->timestamp_mulai)->timezone('Asia/Jakarta') 
            : null;
        $timestampSelesai = $item->timestamp_selesai 
            ? Carbon::parse($item->timestamp_selesai)->timezone('Asia/Jakarta') 
            : null;

        $timestampMulaiFormatted = $timestampMulai?->format('Y-m-d H:i');
        $timestampSelesaiFormatted = $timestampSelesai?->format('Y-m-d H:i');

        // 🔹 Durasi Terlaksana
        $durasiTerlaksana = '-';
        if (!is_null($item->durasi_terlaksana) && is_numeric($item->durasi_terlaksana) && $item->durasi_terlaksana > 0) {
            $durasiTerlaksana = $this->formatDurasi($item->durasi_terlaksana);
        } elseif ($timestampMulai && $timestampSelesai) {
            $minutes = $timestampMulai->diffInMinutes($timestampSelesai);
            $durasiTerlaksana = $this->formatDurasi($minutes);
        }

        // 🔹 Durasi Rekomendasi
        $durasiRekomendasi = '-';
        if (!is_null($item->durasi_rekomendasi) && is_numeric($item->durasi_rekomendasi)) {
            $durasiRekomendasi = $this->formatDurasi($item->durasi_rekomendasi);
        }

        return [
            'process_id' => $item->process_id,
            'nama_jenis' => $item->nama_jenis,
            'timestamp_mulai_mentah' => $timestampMulaiFormatted,
            'timestamp_selesai' => $timestampSelesaiFormatted,
            'berat_gabah_awal' => $item->berat_gabah_awal,
            'berat_gabah_akhir' => $item->berat_gabah_akhir,
            'durasi_rekomendasi' => $durasiRekomendasi,
            'durasi_terlaksana' => $durasiTerlaksana,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $formatted
    ]);
}


private function formatDurasi($menit)
{
    if (!is_numeric($menit) || $menit < 0) {
        return '-';
    }
    $jam = floor($menit / 60);
    $sisaMenit = $menit % 60;
    return $jam . ' jam ' . $sisaMenit . ' menit';
}



    public function validateProcess(Request $request, $process_id)
{
    Log::info('Memulai validasi proses', ['process_id' => $process_id, 'request' => $request->all()]);

    // Validasi input dasar (tidak perlu validasi process_id di body, karena sudah ada di path)
    $validator = Validator::make($request->all(), [
        // 'tanggal_selesai' => 'required|date_format:Y-m-d\TH:i:s', // opsional
        'berat_akhir' => 'required|numeric|min:0|max:99999999',
    ]);

    if ($validator->fails()) {
        Log::error('Validasi input gagal', ['errors' => $validator->errors()]);
        return response()->json([
            'pesan'  => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // Ambil proses (opsional: tambahkan filter user_id jika perlu)
        $dryerProcess = DryerProcess::findOrFail($process_id);
        Log::info('Proses ditemukan', ['process_id' => $process_id, 'data' => $dryerProcess]);

        // (Opsional) jika kamu tetap ingin pakai tanggal_selesai dari request
        // $endTime = \Carbon\Carbon::parse($request->tanggal_selesai)->setTimezone(config('app.timezone'));
        // if ($dryerProcess->timestamp_mulai && $endTime->lessThan($dryerProcess->timestamp_mulai)) {
        //     Log::error('Tanggal selesai tidak valid', ['process_id' => $process_id]);
        //     return response()->json([
        //         'pesan' => 'Tanggal selesai harus setelah tanggal mulai'
        //     ], 422);
        // }

        // ====== VALIDASI: berat akhir tidak boleh > berat awal ======
        $beratAkhir = (float) $request->berat_akhir;
        $beratAwal  = (float) ($dryerProcess->berat_gabah_awal ?? 0);

        if ($beratAkhir > $beratAwal) {
            $msg = 'Berat akhir tidak boleh lebih besar dari berat awal.';
            Log::warning($msg, [
                'process_id' => $process_id,
                'berat_awal' => $beratAwal,
                'berat_akhir'=> $beratAkhir
            ]);

            return response()->json([
                'pesan'  => 'Validasi gagal',
                'errors' => [
                    'berat_akhir' => [
                        $msg . ' (berat_awal: ' . rtrim(rtrim(number_format($beratAwal, 2, '.', ''), '0'), '.') .
                        ', berat_akhir: ' . rtrim(rtrim(number_format($beratAkhir, 2, '.', ''), '0'), '.') . ')'
                    ]
                ]
            ], 422);
        }

        // Hitung durasi aktual (jika ingin dipakai; saat ini tidak disimpan)
        // $durasi_aktual = $dryerProcess->timestamp_mulai
        //     ? $dryerProcess->timestamp_mulai->diffInSeconds($endTime) / 60
        //     : 0;

        // Update hanya field yang diperlukan
        $dryerProcess->update([
            // 'timestamp_selesai' => $endTime,
            'berat_gabah_akhir' => $beratAkhir,
            // 'durasi_aktual'    => (float) $durasi_aktual,
        ]);

        Log::info('Validasi berhasil', [
            'process_id'   => $process_id,
            'updated_data' => $dryerProcess
        ]);

        if ($request->berat_akhir > $dryerProcess->berat_gabah_awal) {
            return response()->json([
                'pesan' => 'Validasi gagal',
                'errors' => [
                    'berat_akhir' => ['Berat gabah akhir tidak boleh lebih dari berat awal.']
                ]
            ], 422);
        }

        return response()->json([
            'pesan' => 'Validasi berhasil',
            'data'  => $dryerProcess
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::error('Proses tidak ditemukan', [
            'process_id' => $process_id,
            'error'      => $e->getMessage()
        ]);
        return response()->json([
            'pesan' => 'Proses tidak ditemukan',
            'error' => 'Proses dengan ID tersebut tidak ada di database'
        ], 404);

    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('Gagal memvalidasi proses: Kesalahan database', [
            'process_id' => $process_id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString()
        ]);
        return response()->json([
            'pesan' => 'Gagal memvalidasi proses',
            'error' => 'Kesalahan database: ' . $e->getMessage()
        ], 500);

    } catch (\Exception $e) {
        Log::error('Gagal memvalidasi proses: Kesalahan umum', [
            'process_id' => $process_id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString()
        ]);
        return response()->json([
            'pesan' => 'Gagal memvalidasi proses',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function complete(Request $request, $processId)
{
    try {
        // Ambil proses yang masih ongoing (opsional: tambahkan filter user_id jika diperlukan)
        $process = DryerProcess::where('process_id', $processId)->firstOrFail();

        if ($process->status !== 'ongoing') {
            Log::error("Process is not in ongoing status", [
                'process_id' => $processId,
                'status'     => $process->status
            ]);
            return response()->json(['error' => 'Proses tidak dalam status berjalan'], 400);
        }

        // Validasi input dari user (timestamp_selesai tidak diperlukan lagi)
        $validated = $request->validate([
            'kadar_air_akhir'   => 'required|numeric|min:0|max:100',
            'berat_gabah_akhir' => 'sometimes|nullable|numeric|min:0|max:99999999',
        ]);

        // Gunakan waktu server sebagai waktu selesai
        // Jika ingin pakai timezone Asia/Jakarta di level app, pastikan 'timezone' => 'Asia/Jakarta' di config/app.php
        // Di sini kita tetap gunakan now() (mengikuti timezone default app)
        $endTime   = now();
        $startTime = Carbon::parse($process->timestamp_mulai);

        // Safety: jika karena alasan apapun endTime < startTime, paksa endTime = startTime
        if ($endTime->lt($startTime)) {
            Log::warning("End time is earlier than start time; forcing equal times.", [
                'process_id'        => $processId,
                'timestamp_mulai'   => $process->timestamp_mulai,
                'calculated_selesai'=> $endTime->toDateTimeString(),
            ]);
            $endTime = $startTime->copy();
        }

        // Hitung durasi terlaksana (menit), pastikan non-negatif
        $durasiTerlaksana = max(0, $startTime->diffInMinutes($endTime, false));

        // Siapkan data update
        $updateData = [
            'kadar_air_akhir'   => $validated['kadar_air_akhir'],
            'timestamp_selesai' => $endTime,
            'durasi_terlaksana' => $durasiTerlaksana,
            'status'            => 'completed',
        ];

        if (array_key_exists('berat_gabah_akhir', $validated)) {
            $updateData['berat_gabah_akhir'] = $validated['berat_gabah_akhir'];
        }

        $process->update($updateData);

        // Ambil data sensor terbaru untuk respons
        $latestSensorData = SensorData::where('process_id', $processId)
            ->latest('timestamp')
            ->first();

        Log::info("Drying process completed", [
            'process_id'         => $processId,
            'updated_data'       => $process->toArray(),
            'latest_sensor_data' => $latestSensorData ? $latestSensorData->toArray() : null,
            'request_data'       => $request->all()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proses pengeringan selesai',
            'data'    => [
                'process_id'        => $process->process_id,
                'kadar_air_akhir'   => $process->kadar_air_akhir,
                'timestamp_selesai' => $process->timestamp_selesai, // otomatis dari server
                'durasi_terlaksana' => $process->durasi_terlaksana,
                'status'            => $process->status,
                'latest_sensor_data' => $latestSensorData ? [
                    'kadar_air_gabah'    => $latestSensorData->kadar_air_gabah,
                    'suhu_gabah'         => $latestSensorData->suhu_gabah,
                    'suhu_ruangan'       => $latestSensorData->suhu_ruangan,
                    'suhu_pembakaran'    => $latestSensorData->suhu_pembakaran,
                    'timestamp'          => $latestSensorData->timestamp,
                ] : null,
            ]
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error("Validation failed in complete process", [
            'process_id'   => $processId,
            'errors'       => $e->errors(),
            'request_data' => $request->all()
        ]);
        return response()->json(['error' => $e->errors()], 422);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::error("Process not found", [
            'process_id' => $processId,
            'error'      => $e->getMessage()
        ]);
        return response()->json(['error' => 'Proses tidak ditemukan'], 404);

    } catch (\Exception $e) {
        Log::error("Failed to complete process", [
            'process_id'   => $processId,
            'error'        => $e->getMessage(),
            'trace'        => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        return response()->json(['error' => 'Gagal menyelesaikan proses: ' . $e->getMessage()], 500);
    }
}


    public function detail(Request $request, $process_id)
    {
        $process = DryerProcess::find($process_id);
        if (!$process) {
            return response()->json([
                'status' => 'error',
                'message' => 'Process not found'
            ], 404);
        }

        $estimations = PredictionEstimation::where('process_id', $process_id)
            ->select('id', 'process_id', 'estimasi_durasi', 'timestamp')
            ->orderByDesc('timestamp') // ✅ urutkan dari terbaru
            ->get();

        $sensorData = SensorData::where('process_id', $process_id)
            ->join('sensor_devices', 'sensor_data.device_id', '=', 'sensor_devices.device_id')
            ->select(
                'sensor_data.sensor_id',
                'sensor_data.process_id',
                'sensor_data.device_id',
                'sensor_data.timestamp',
                'sensor_data.kadar_air_gabah',
                'sensor_data.suhu_gabah',
                'sensor_data.suhu_ruangan',
                'sensor_data.suhu_pembakaran',
                'sensor_data.status_pengaduk',
                'sensor_devices.device_name'
            )
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->timestamp)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            });

        $formatted = collect();
        $interval = $estimations->count(); // ✅ dimulai dari angka terbesar

        foreach ($estimations as $estimation) {
            $estimationTimestamp = Carbon::parse($estimation->timestamp)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            if (!$sensorData->has($estimationTimestamp)) {
                $interval--; // tetap kurangi agar interval tetap konsisten
                continue;
            }

            $matchedSensors = $sensorData[$estimationTimestamp];

            $tombak = [];
            $pembakaran_pengaduk = [];

            foreach ($matchedSensors as $sensor) {
                $sensorFormatted = [
                    'sensor_id' => $sensor->sensor_id,
                    'timestamp' => $estimationTimestamp,
                    'device_name' => $sensor->device_name
                ];

                if (!is_null($sensor->kadar_air_gabah) || !is_null($sensor->suhu_gabah) || !is_null($sensor->suhu_ruangan)) {
                    $sensorFormatted['kadar_air_gabah'] = $sensor->kadar_air_gabah ?? '-';
                    $sensorFormatted['suhu_gabah'] = $sensor->suhu_gabah ?? '-';
                    $sensorFormatted['suhu_ruangan'] = $sensor->suhu_ruangan ?? '-';
                    $tombak[] = $sensorFormatted;
                }

                if (!is_null($sensor->suhu_pembakaran) || !is_null($sensor->status_pengaduk)) {
                    $sensorFormatted['suhu_pembakaran'] = $sensor->suhu_pembakaran ?? '-';
                    $sensorFormatted['status_pengaduk'] = !is_null($sensor->status_pengaduk)
                        ? ($sensor->status_pengaduk ? 'Aktif' : 'Nonaktif')
                        : '-';
                    $pembakaran_pengaduk[] = $sensorFormatted;
                }
            }

            if (empty($tombak) && empty($pembakaran_pengaduk)) {
                $interval--; // tetap kurangi walau tidak dipush
                continue;
            }

            $formatted->push([
                'interval' => $interval--, // ✅ interval mundur
                'estimation_id' => $estimation->id,
                'process_id' => $estimation->process_id,
                'estimasi_durasi' => round($estimation->estimasi_durasi) . ' menit',
                'timestamp' => $estimationTimestamp,
                'suhu_pembakaran' => $pembakaran_pengaduk[0]['suhu_pembakaran'] ?? '-',
                'status_pengaduk' => $pembakaran_pengaduk[0]['status_pengaduk'] ?? '-',
                'suhu_ruangan' => $tombak[0]['suhu_ruangan'] ?? '-',
                'kadar_air_gabah' => $tombak[0]['kadar_air_gabah'] ?? '-',
                'suhu_gabah' => $tombak[0]['suhu_gabah'] ?? '-',
                'kadar_air_rata' => $this->calculateAverageKadarAir($tombak),
                'sensor_data' => [
                    'tombak' => $tombak,
                    'pembakaran_pengaduk' => $pembakaran_pengaduk
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $formatted->values() // ✅ urutan dari besar ke kecil
        ]);
    }

    private function calculateAverageKadarAir($tombak)
    {
        $total = 0;
        $count = 0;

        foreach ($tombak as $item) {
            if (is_numeric($item['kadar_air_gabah'])) {
                $total += $item['kadar_air_gabah'];
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 2) : '-';
    }
}
