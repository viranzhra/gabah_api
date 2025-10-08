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
        try {
            $query = DryerProcess::query()
                ->leftJoin('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
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
                    'drying_process.avg_estimasi_durasi',
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
                $query->whereIn('drying_process.status', ['completed']);
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
                    'nama_jenis' => $item->nama_jenis ?? '-',
                    'timestamp_mulai_mentah' => $timestampMulaiFormatted,
                    'timestamp_selesai' => $timestampSelesaiFormatted,
                    'berat_gabah_awal' => is_numeric($item->berat_gabah_awal) ? floatval($item->berat_gabah_awal) : null,
                    'berat_gabah_akhir' => is_numeric($item->berat_gabah_akhir) ? floatval($item->berat_gabah_akhir) : null,
                    'kadar_air_awal' => is_numeric($item->kadar_air_awal) ? floatval($item->kadar_air_awal) : null,
                    'kadar_air_akhir' => is_numeric($item->kadar_air_akhir) ? floatval($item->kadar_air_akhir) : null,
                    'durasi_rekomendasi' => $durasiRekomendasi,
                    'durasi_terlaksana' => $durasiTerlaksana,
                    'status' => $item->status,
                    'avg_estimasi_durasi' => is_numeric($item->avg_estimasi_durasi) ? floatval($item->avg_estimasi_durasi) : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formatted
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in riwayat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan database: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('General error in riwayat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
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
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dryerProcess = DryerProcess::where('process_id', $process_id)
                ->where('status', 'completed')
                ->firstOrFail();

            $beratAkhir = (float) $request->berat_gabah_akhir;
            $beratAwal = is_numeric($dryerProcess->berat_gabah_awal) ? (float) $dryerProcess->berat_gabah_awal : 0;

            if ($beratAkhir > $beratAwal) {
                $msg = 'Berat akhir tidak boleh lebih besar dari berat awal.';
                Log::warning($msg, [
                    'process_id' => $process_id,
                    'berat_gabah_awal' => $beratAwal,
                    'berat_gabah_akhir' => $beratAkhir
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => [
                        'berat_gabah_akhir' => [
                            $msg . ' (berat_gabah_awal: ' . number_format($beratAwal, 2) .
                            ', berat_gabah_akhir: ' . number_format($beratAkhir, 2) . ')'
                        ]
                    ]
                ], 422);
            }

            // Hitung durasi terlaksana
            $durasiTerlaksana = $dryerProcess->durasi_terlaksana;
            if ($dryerProcess->timestamp_mulai && $dryerProcess->timestamp_selesai) {
                $durasiTerlaksana = Carbon::parse($dryerProcess->timestamp_mulai)
                    ->diffInMinutes(Carbon::parse($dryerProcess->timestamp_selesai));
            }

            $dryerProcess->update([
                'berat_gabah_akhir' => $beratAkhir,
                'status' => 'completed',
                'durasi_terlaksana' => $durasiTerlaksana,
                'timestamp_selesai' => $dryerProcess->timestamp_selesai ?? Carbon::now('Asia/Jakarta'),
            ]);

            Log::info('Validasi berhasil', [
                'process_id' => $process_id,
                'updated_data' => $dryerProcess
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Validasi berhasil',
                'data' => $dryerProcess
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Proses tidak ditemukan atau bukan status ongoing', [
                'process_id' => $process_id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Proses tidak ditemukan atau sudah selesai',
                'error' => 'Proses dengan ID tersebut tidak ada atau bukan status ongoing'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Gagal memvalidasi proses: Kesalahan database', [
                'process_id' => $process_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Kesalahan database: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Gagal memvalidasi proses: Kesalahan umum', [
                'process_id' => $process_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatDurasi($minutes)
    {
        if (!is_numeric($minutes) || $minutes <= 0) {
            return '-';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return ($hours > 0 ? $hours . ' jam ' : '') . ($remainingMinutes > 0 ? $remainingMinutes . ' menit' : '');
    }
    
    public function complete(Request $request, $processId)
    {
        try {
            Log::info("Complete process called", [
                'process_id' => $processId,
                'request_data' => $request->all(),
            ]);

            $process = DryerProcess::where('process_id', $processId)->firstOrFail();
            
            Log::info("Process found", [
                'process_id' => $process->process_id,
                'status' => $process->status,
                'dryer_id' => $process->dryer_id,
                'timestamp_mulai' => $process->timestamp_mulai,
                'timestamp_type' => gettype($process->timestamp_mulai)
            ]);

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

            Log::info("Validation passed", [
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'kadar_air_type' => gettype($validated['kadar_air_akhir']),
                'berat_gabah_akhir' => $validated['berat_gabah_akhir'] ?? null
            ]);

            // ✅ PERBAIKAN: Handle timestamp_mulai dengan aman
            $timestampMulai = $process->timestamp_mulai;
            if (!$timestampMulai) {
                Log::error("No start timestamp found", ['process_id' => $processId]);
                return response()->json(['error' => 'Timestamp mulai tidak ditemukan'], 400);
            }

            // Parse timestamp dengan Carbon - handle string atau Carbon instance
            $startTime = Carbon::parse($timestampMulai);
            $endTime = Carbon::now();

            Log::info("Timestamps parsed", [
                'process_id' => $processId,
                'start_time_raw' => $timestampMulai,
                'start_time_parsed' => $startTime->toDateTimeString(),
                'end_time' => $endTime->toDateTimeString(),
                'start_time_type' => gettype($startTime)
            ]);

            if ($endTime->lt($startTime)) {
                Log::warning("End time is earlier than start time; forcing equal times.", [
                    'process_id' => $processId,
                    'timestamp_mulai' => $timestampMulai,
                    'calculated_selesai' => $endTime->toDateTimeString(),
                ]);
                $endTime = $startTime->copy();
            }

            // ✅ PERBAIKAN: Cast durasi ke integer
            $durasiTerlaksanaFloat = $startTime->diffInMinutes($endTime, false);
            $durasiTerlaksana = (int) max(0, $durasiTerlaksanaFloat);

            Log::info("Duration calculation", [
                'process_id' => $processId,
                'durasi_float' => $durasiTerlaksanaFloat,
                'durasi_integer' => $durasiTerlaksana,
                'durasi_type' => gettype($durasiTerlaksana)
            ]);

            // Update data dengan type casting
            $updateData = [
                'kadar_air_akhir' => (float) $validated['kadar_air_akhir'],
                'timestamp_selesai' => $endTime->toDateTimeString(), // ✅ Convert ke string sejak awal
                'durasi_terlaksana' => $durasiTerlaksana,
                'status' => 'completed',
            ];

            if (array_key_exists('berat_gabah_akhir', $validated)) {
                $updateData['berat_gabah_akhir'] = (float) $validated['berat_gabah_akhir'];
            }

            Log::info("Update data prepared", [
                'process_id' => $processId,
                'update_data' => $updateData,
                'types' => array_map('gettype', $updateData)
            ]);

            // Update process
            $process->update($updateData);

            // ✅ PERBAIKAN: Refresh model
            $updatedProcess = $process->fresh();

            Log::info("Process updated successfully", [
                'process_id' => $processId,
                'new_status' => $updatedProcess->status,
                'timestamp_selesai_raw' => $updatedProcess->timestamp_selesai,
                'timestamp_selesai_type' => gettype($updatedProcess->timestamp_selesai),
                'kadar_air_akhir' => $updatedProcess->kadar_air_akhir,
                'durasi_terlaksana' => $updatedProcess->durasi_terlaksana,
                'durasi_type' => gettype($updatedProcess->durasi_terlaksana)
            ]);

            // Ambil latest sensor data dengan error handling
            $latestSensorData = null;
            try {
                $latestSensorData = SensorData::where('process_id', $processId)
                    ->orderBy('timestamp', 'desc')
                    ->first();
            } catch (\Exception $sensorErr) {
                Log::warning("Failed to get latest sensor data", [
                    'process_id' => $processId,
                    'error' => $sensorErr->getMessage()
                ]);
            }

            // ✅ PERBAIKAN: Format response dengan safe datetime handling
            $timestampSelesaiFormatted = null;
            if ($updatedProcess->timestamp_selesai) {
                // Handle string atau Carbon instance
                if ($updatedProcess->timestamp_selesai instanceof Carbon) {
                    $timestampSelesaiFormatted = $updatedProcess->timestamp_selesai->toDateTimeString();
                } else {
                    // Jika sudah string, validasi format dan format ulang jika perlu
                    $timestampSelesaiFormatted = Carbon::parse($updatedProcess->timestamp_selesai)->toDateTimeString();
                }
            }

            $responseData = [
                'process_id' => (int) $updatedProcess->process_id,
                'kadar_air_akhir' => (float) $updatedProcess->kadar_air_akhir,
                'timestamp_selesai' => $timestampSelesaiFormatted,
                'durasi_terlaksana' => (int) $updatedProcess->durasi_terlaksana,
                'status' => $updatedProcess->status,
            ];

            // Sensor data dengan safe casting
            if ($latestSensorData) {
                $sensorTimestamp = null;
                if ($latestSensorData->timestamp) {
                    if ($latestSensorData->timestamp instanceof Carbon) {
                        $sensorTimestamp = $latestSensorData->timestamp->toDateTimeString();
                    } else {
                        $sensorTimestamp = Carbon::parse($latestSensorData->timestamp)->toDateTimeString();
                    }
                }

                $responseData['latest_sensor_data'] = [
                    'kadar_air_gabah' => (float) ($latestSensorData->kadar_air_gabah ?? 0),
                    'suhu_gabah' => (float) ($latestSensorData->suhu_gabah ?? 0),
                    'suhu_ruangan' => (float) ($latestSensorData->suhu_ruangan ?? 0),
                    'suhu_pembakaran' => $latestSensorData->suhu_pembakaran ? (float) $latestSensorData->suhu_pembakaran : null,
                    'timestamp' => $sensorTimestamp,
                ];
            }

            Log::info("Drying process completed", [
                'process_id' => $processId,
                'response_data' => $responseData,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proses pengeringan selesai',
                'data' => $responseData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation failed in complete process", [
                'process_id' => $processId,
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'error' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
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
                'error_code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'error' => 'Gagal menyelesaikan proses: ' . $e->getMessage()
            ], 500);
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

    // private function formatDurasi($menit)
    // {
    //     if (!is_numeric($menit) || $menit < 0) {
    //         return '-';
    //     }
    //     $jam = floor($menit / 60);
    //     $sisaMenit = $menit % 60;
    //     return $jam . ' jam ' . $sisaMenit . ' menit';
    // }

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
                'interval' => $interval--,
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
                ],
                'has_tombak' => !empty($tombak),
                'has_pembakaran' => !empty($pembakaran_pengaduk),
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