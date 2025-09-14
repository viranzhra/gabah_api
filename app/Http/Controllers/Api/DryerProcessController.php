<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryerProcess;
use App\Models\SensorData;
use App\Models\PredictionEstimation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class DryerProcessController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Remove user_id filter since it's not in the schema
            $process = DryerProcess::query()
                ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
                ->select(
                    'drying_process.process_id',
                    'drying_process.dryer_id',
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
            Log::error("Database error in index: " . $e->getMessage());
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

    public function startDryingProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dryer_id' => 'required|exists:bed_dryers,dryer_id', // Required per schema
            'grain_type_id' => 'nullable|exists:grain_types,grain_type_id', // Nullable per schema
            'kadar_air_target' => 'required|numeric|min:0|max:100',
            'lokasi' => 'nullable|string|max:100', // Assuming lokasi is stored in catatan or elsewhere
            'berat_gabah_awal' => 'nullable|numeric|min:0',
            'kadar_air_awal' => 'nullable|numeric|min:0|max:100',
            'durasi_rekomendasi' => 'nullable|integer|min:0', // Integer per schema
            'timestamp_mulai' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for startDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Check for pending process (no user_id filter)
            $existingProcess = DryerProcess::where('status', 'pending')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($existingProcess) {
                $existingProcess->update([
                    'dryer_id' => $request->dryer_id,
                    'grain_type_id' => $request->grain_type_id,
                    'kadar_air_target' => $request->kadar_air_target,
                    'catatan' => $request->lokasi, // Map lokasi to catatan
                    'berat_gabah_awal' => $request->berat_gabah_awal,
                    'kadar_air_awal' => $request->kadar_air_awal,
                    'durasi_rekomendasi' => $request->durasi_rekomendasi ?? 0,
                    'status' => 'ongoing',
                    'timestamp_mulai' => $request->timestamp_mulai ?? now()->format('Y-m-d H:i:s'),
                ]);

                Log::info("Pending drying process updated to ongoing: " . json_encode($existingProcess->toArray()));
                return response()->json([
                    'message' => 'Pending process updated successfully',
                    'process_id' => $existingProcess->process_id,
                ], 200);
            }

            // Create new process
            $process = DryerProcess::create([
                'dryer_id' => $request->dryer_id,
                'grain_type_id' => $request->grain_type_id,
                'kadar_air_target' => $request->kadar_air_target,
                'catatan' => $request->lokasi, // Map lokasi to catatan
                'berat_gabah_awal' => $request->berat_gabah_awal,
                'kadar_air_awal' => $request->kadar_air_awal,
                'durasi_rekomendasi' => $request->durasi_rekomendasi ?? 0,
                'status' => 'ongoing',
                'timestamp_mulai' => $request->timestamp_mulai ?? now()->format('Y-m-d H:i:s'),
            ]);

            Log::info("New drying process started: " . json_encode($process->toArray()));
            return response()->json([
                'message' => 'Drying process started successfully',
                'process_id' => $process->process_id,
            ], 201);
        } catch (QueryException $e) {
            Log::error("Database error in startDryingProcess: " . $e->getMessage());
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                return response()->json(['error' => 'Invalid dryer_id or grain_type_id'], 422);
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
            'dryer_id' => 'nullable|exists:bed_dryers,dryer_id',
            'grain_type_id' => 'nullable|exists:grain_types,grain_type_id',
            'kadar_air_target' => 'nullable|numeric|min:0|max:100',
            'berat_gabah_awal' => 'nullable|numeric|min:0',
            'berat_gabah_akhir' => 'nullable|numeric|min:0',
            'kadar_air_awal' => 'nullable|numeric|min:0|max:100',
            'kadar_air_akhir' => 'nullable|numeric|min:0|max:100',
            'durasi_rekomendasi' => 'nullable|integer|min:0',
            'durasi_terlaksana' => 'nullable|integer|min:0',
            'avg_estimasi_durasi' => 'nullable|numeric|min:0',
            'timestamp_selesai' => 'nullable|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:pending,ongoing,completed',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for updateDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $process = DryerProcess::findOrFail($request->process_id);

            $fields = array_filter([
                'dryer_id' => $request->dryer_id,
                'grain_type_id' => $request->grain_type_id,
                'kadar_air_target' => $request->kadar_air_target,
                'berat_gabah_awal' => $request->berat_gabah_awal,
                'berat_gabah_akhir' => $request->berat_gabah_akhir,
                'kadar_air_awal' => $request->kadar_air_awal,
                'kadar_air_akhir' => $request->kadar_air_akhir,
                'durasi_rekomendasi' => $request->durasi_rekomendasi,
                'durasi_terlaksana' => $request->durasi_terlaksana,
                'avg_estimasi_durasi' => $request->avg_estimasi_durasi,
                'timestamp_selesai' => $request->timestamp_selesai,
                'status' => $request->status,
                'catatan' => $request->catatan,
            ]);

            $process->update($fields);
            $updatedProcess = $process->fresh();

            Log::info("Drying process updated: " . json_encode($updatedProcess->toArray()));
            return response()->json(['message' => 'Drying process updated successfully', 'data' => $updatedProcess], 200);
        } catch (QueryException $e) {
            Log::error("Database error in updateDryingProcess: " . $e->getMessage());
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function riwayat(Request $request)
    {
        $query = DryerProcess::query()
            ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
            ->select(
                'drying_process.process_id',
                'drying_process.dryer_id',
                'drying_process.grain_type_id',
                'drying_process.kadar_air_target',
                'drying_process.berat_gabah_awal',
                'drying_process.berat_gabah_akhir',
                'drying_process.kadar_air_awal',
                'drying_process.kadar_air_akhir',
                'drying_process.durasi_rekomendasi',
                'drying_process.durasi_terlaksana',
                'drying_process.status',
                'drying_process.timestamp_mulai',
                'drying_process.timestamp_selesai',
                'drying_process.catatan',
                'grain_types.nama_jenis'
            );

        // Filter by process_id if provided
        if ($request->has('process_id')) {
            $query->where('drying_process.process_id', $request->input('process_id'));
        } else {
            $query->where('drying_process.status', 'completed');
        }

        // Filter by date if provided
        if ($request->has('filter_tanggal')) {
            $query->whereDate('drying_process.timestamp_mulai', $request->input('filter_tanggal'));
        }

        $data = $query->orderByDesc('drying_process.timestamp_mulai')->get();

        $formatted = $data->map(function ($item) {
            $timestampMulai = $item->timestamp_mulai
                ? Carbon::parse($item->timestamp_mulai)->timezone('Asia/Jakarta')
                : null;
            $timestampSelesai = $item->timestamp_selesai
                ? Carbon::parse($item->timestamp_selesai)->timezone('Asia/Jakarta')
                : null;

            $timestampMulaiFormatted = $timestampMulai ? $timestampMulai->format('Y-m-d H:i') : '-';
            $timestampSelesaiFormatted = $timestampSelesai ? $timestampSelesai->format('Y-m-d H:i') : '-';

            $durasiTerlaksana = '-';
            if (!is_null($item->durasi_terlaksana) && is_numeric($item->durasi_terlaksana) && $item->durasi_terlaksana > 0) {
                $durasiTerlaksana = $this->formatDurasi($item->durasi_terlaksana);
            } elseif ($timestampMulai && $timestampSelesai) {
                $minutes = $timestampMulai->diffInMinutes($timestampSelesai);
                $durasiTerlaksana = $this->formatDurasi($minutes);
            }

            $durasiRekomendasi = !is_null($item->durasi_rekomendasi) && is_numeric($item->durasi_rekomendasi)
                ? $this->formatDurasi($item->durasi_rekomendasi)
                : '-';

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

    public function validateProcess(Request $request, $process_id)
    {
        Log::info('Memulai validasi proses', ['process_id' => $process_id, 'request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'berat_gabah_akhir' => 'required|numeric|min:0|max:99999999',
        ]);

        if ($validator->fails()) {
            Log::error('Validasi input gagal', ['errors' => $validator->errors()]);
            return response()->json([
                'pesan' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dryerProcess = DryerProcess::findOrFail($process_id);

            $beratAkhir = (float) $request->berat_gabah_akhir;
            $beratAwal = (float) ($dryerProcess->berat_gabah_awal ?? 0);

            if ($beratAkhir > $beratAwal) {
                $msg = 'Berat akhir tidak boleh lebih besar dari berat awal.';
                Log::warning($msg, [
                    'process_id' => $process_id,
                    'berat_gabah_awal' => $beratAwal,
                    'berat_gabah_akhir' => $beratAkhir
                ]);

                return response()->json([
                    'pesan' => 'Validasi gagal',
                    'errors' => [
                        'berat_gabah_akhir' => [
                            $msg . ' (berat_gabah_awal: ' . rtrim(rtrim(number_format($beratAwal, 2, '.', ''), '0'), '.') .
                            ', berat_gabah_akhir: ' . rtrim(rtrim(number_format($beratAkhir, 2, '.', ''), '0'), '.') . ')'
                        ]
                    ]
                ], 422);
            }

            $dryerProcess->update([
                'berat_gabah_akhir' => $beratAkhir,
            ]);

            Log::info('Validasi berhasil', [
                'process_id' => $process_id,
                'updated_data' => $dryerProcess
            ]);

            return response()->json([
                'pesan' => 'Validasi berhasil',
                'data' => $dryerProcess
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Proses tidak ditemukan', [
                'process_id' => $process_id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'pesan' => 'Proses tidak ditemukan',
                'error' => 'Proses dengan ID tersebut tidak ada di database'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Gagal memvalidasi proses: Kesalahan database', [
                'process_id' => $process_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'pesan' => 'Gagal memvalidasi proses',
                'error' => 'Kesalahan database: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Gagal memvalidasi proses: Kesalahan umum', [
                'process_id' => $process_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            $process = DryerProcess::where('process_id', $processId)->firstOrFail();

            if ($process->status !== 'ongoing') {
                Log::error("Process is not in ongoing status", [
                    'process_id' => $processId,
                    'status' => $process->status
                ]);
                return response()->json(['error' => 'Proses tidak dalam status berjalan'], 400);
            }

            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'berat_gabah_akhir' => 'nullable|numeric|min:0|max:99999999',
            ]);

            $endTime = now();
            $startTime = Carbon::parse($process->timestamp_mulai);

            if ($endTime->lt($startTime)) {
                Log::warning("End time is earlier than start time; forcing equal times.", [
                    'process_id' => $processId,
                    'timestamp_mulai' => $process->timestamp_mulai,
                    'calculated_selesai' => $endTime->toDateTimeString(),
                ]);
                $endTime = $startTime->copy();
            }

            $durasiTerlaksana = max(0, $startTime->diffInMinutes($endTime, false));

            $updateData = [
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'timestamp_selesai' => $endTime,
                'durasi_terlaksana' => $durasiTerlaksana,
                'status' => 'completed',
            ];

            if (array_key_exists('berat_gabah_akhir', $validated)) {
                $updateData['berat_gabah_akhir'] = $validated['berat_gabah_akhir'];
            }

            $process->update($updateData);

            $latestSensorData = SensorData::where('process_id', $processId)
                ->latest('timestamp')
                ->first();

            Log::info("Drying process completed", [
                'process_id' => $processId,
                'updated_data' => $process->toArray(),
                'latest_sensor_data' => $latestSensorData ? $latestSensorData->toArray() : null,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proses pengeringan selesai',
                'data' => [
                    'process_id' => $process->process_id,
                    'kadar_air_akhir' => $process->kadar_air_akhir,
                    'timestamp_selesai' => $process->timestamp_selesai,
                    'durasi_terlaksana' => $process->durasi_terlaksana,
                    'status' => $process->status,
                    'latest_sensor_data' => $latestSensorData ? [
                        'kadar_air_gabah' => $latestSensorData->kadar_air_gabah,
                        'suhu_gabah' => $latestSensorData->suhu_gabah,
                        'suhu_ruangan' => $latestSensorData->suhu_ruangan,
                        'suhu_pembakaran' => $latestSensorData->suhu_pembakaran,
                        'timestamp' => $latestSensorData->timestamp,
                    ] : null,
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation failed in complete process", [
                'process_id' => $processId,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Process not found", [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Proses tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error("Failed to complete process", [
                'process_id' => $processId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Gagal menyelesaikan proses: ' . $e->getMessage()], 500);
        }
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
                'drying_process.dryer_id',
                'drying_process.grain_type_id',
                'drying_process.kadar_air_target',
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

    private function formatDurasi($menit)
    {
        if (!is_numeric($menit) || $menit < 0) {
            return '-';
        }
        $jam = floor($menit / 60);
        $sisaMenit = $menit % 60;
        return $jam . ' jam ' . $sisaMenit . ' menit';
    }

}