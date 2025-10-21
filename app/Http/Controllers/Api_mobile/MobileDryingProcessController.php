<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\GrainType;
use App\Models\BedDryer;
use Carbon\Carbon;

class MobileDryingProcessController extends Controller
{
    public function getHistory(Request $request)
    {
        try {
            // pastikan user login (untuk cek kepemilikan dryer)
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // users table kamu pakai kolom user_id, jadi fallback kalau $user->id tidak ada
            $userId  = $user->id ?? $user->user_id;
            $dryerId = $request->query('dryer_id'); // opsional

            // jika dryer_id dikirim, validasi dryer tsb milik user
            if (!empty($dryerId)) {
                $ownsDryer = BedDryer::where('dryer_id', $dryerId)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$ownsDryer) {
                    // dryer bukan milik user → kembalikan kosong (200)
                    return response()->json([
                        'success' => true,
                        'data' => [],
                    ], 200);
                }
            }

            // Ambil riwayat proses SELESAI
            // - jika dryer_id ada: filter dryer_id itu (sudah dipastikan milik user)
            // - jika dryer_id tidak ada: batasi ke semua dryer yang dimiliki user
            $query = DryingProcess::query()
                ->where('status', 'completed')
                ->when(!empty($dryerId), fn ($q) => $q->where('dryer_id', $dryerId))
                ->when(empty($dryerId), function ($q) use ($userId) {
                    $q->whereHas('bedDryer', fn ($dq) => $dq->where('user_id', $userId));
                })
                ->with(['grainType', 'bedDryer.warehouse'])
                ->orderBy('timestamp_mulai', 'desc');

            // gunakan translatedFormat agar bulan berbahasa Indonesia
            Carbon::setLocale('id');

            $processes = $query->get()
                ->groupBy(function ($p) {
                    return Carbon::parse($p->timestamp_mulai)->translatedFormat('d F Y');
                });

            $history = $processes->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'entries' => $group->map(function ($process) {
                        $firstSensor = SensorData::where('process_id', $process->process_id)
                            ->orderBy('timestamp', 'asc')
                            ->first();

                        $lastSensor = SensorData::where('process_id', $process->process_id)
                            ->orderBy('timestamp', 'desc')
                            ->first();

                        $dryerName = optional($process->dryer)->nama
                            ?? ($process->dryer_name ?? 'Bed Dryer');
                        $warehouseName = optional(optional($process->dryer)->warehouse)->nama
                            ?? ($process->lokasi ?? 'Gudang Utama');

                        return [
                            'process_id'        => $process->process_id,
                            'dryer_id'          => $process->dryer_id,
                            'dryer_name'        => $dryerName,
                            'warehouse'         => $warehouseName,

                            'grainType'         => optional($process->grainType)->nama_jenis ?? 'Unknown',
                            'startDate'         => Carbon::parse($process->timestamp_mulai)->translatedFormat('d F Y'),
                            'startDateFull'     => Carbon::parse($process->timestamp_mulai)->format('Y-m-d H:i:s'),
                            'endDate'           => $process->timestamp_selesai
                                ? Carbon::parse($process->timestamp_selesai)->translatedFormat('d F Y')
                                : 'N/A',
                            'startTime'         => Carbon::parse($process->timestamp_mulai)->format('H:i'),
                            'endTime'           => $process->timestamp_selesai
                                ? Carbon::parse($process->timestamp_selesai)->format('H:i')
                                : 'N/A',
                            'initialWeight'     => $firstSensor ? $process->berat_gabah_awal : 'N/A',
                            'finalWeight'       => $lastSensor ? $process->berat_gabah_akhir : 'N/A',
                            'estimatedDuration' => $this->formatDuration($process->durasi_rekomendasi),
                            'executedDuration'  => $this->formatDuration($process->durasi_terlaksana),
                            'actualDuration'    => $process->durasi_aktual
                                ? $this->formatDuration($process->durasi_aktual)
                                : 'N/A',
                            'status'            => ucfirst(strtolower($process->status)),
                            'location'          => $warehouseName, // kompatibel dengan UI lama
                            'notes'             => $process->catatan ?? '',
                        ];
                    })->toArray(),
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'data'    => $history,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getProcessDetails(Request $request, $processId)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $userId    = $user->id ?? $user->user_id;
            $perPage   = (int) $request->query('per_page', 10);
            $page      = (int) $request->query('page', 1);
            $latest    = filter_var($request->query('latest', false), FILTER_VALIDATE_BOOLEAN);
            $sortOrder = $request->query('sort_order', 'desc') === 'asc' ? 'asc' : 'desc';

            // Ambil proses (tanpa where user_id di proses), lalu validasi kepemilikan via bed_dryers
            $process = DryingProcess::with(['grainType', 'bedDryer'])
                ->where('process_id', $processId)
                ->first();

            if (!$process) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proses pengeringan tidak ditemukan.',
                ], 404);
            }

            // Validasi: dryer proses ini harus milik user yang login
            if ($process->dryer_id) {
                $ownsDryer = BedDryer::where('dryer_id', $process->dryer_id)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$ownsDryer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak berhak mengakses proses ini.',
                    ], 403);
                }
            }

            // Ambil data sensor sesuai urutan
            $sensorData = SensorData::where('process_id', $processId)
                ->with('sensorDevice')
                ->orderBy('timestamp', $sortOrder)
                ->get();

            Carbon::setLocale('id');

            // Kelompokkan berdasarkan timestamp tanpa milidetik
            $grouped = $sensorData->groupBy(function ($d) {
                return Carbon::parse($d->timestamp)->format('Y-m-d H:i:s');
            });

            $totalRecords = $grouped->count();

            $formatted = $grouped
                ->sortBy(function ($group, $ts) use ($sortOrder) {
                    return $sortOrder === 'asc'
                        ? Carbon::parse($ts)->timestamp
                        : -Carbon::parse($ts)->timestamp;
                })
                ->values()
                ->map(function ($group, $index) use ($totalRecords, $sortOrder) {
                    $intervalData = [];
                    $moistures = [];

                    $interval = $sortOrder === 'asc' ? $index + 1 : $totalRecords - $index;

                    foreach ($group as $row) {
                        $device = $row->sensorDevice->device_name ?? 'Unknown';
                        $payload = [];

                        if (!is_null($row->suhu_pembakaran)) {
                            $payload['burning_temperature'] = number_format((float) $row->suhu_pembakaran, 2, '.', '');
                        }
                        if (!is_null($row->suhu_ruangan)) {
                            $payload['room_temperature'] = number_format((float) $row->suhu_ruangan, 2, '.', '');
                        }
                        if (!is_null($row->kadar_air_gabah)) {
                            $payload['grain_moisture'] = number_format((float) $row->kadar_air_gabah, 2, '.', '');
                            $moistures[] = (float) $row->kadar_air_gabah;
                        }
                        if (!is_null($row->suhu_gabah)) {
                            $payload['grain_temperature'] = number_format((float) $row->suhu_gabah, 2, '.', '');
                        }
                        if (!is_null($row->berat_gabah)) {
                            $payload['weight'] = number_format((float) $row->berat_gabah, 2, '.', '');
                        }
                        if (!is_null($row->status_pengaduk)) {
                            $payload['stirrer_status'] = (bool) $row->status_pengaduk;
                        }

                        if (!empty($payload)) {
                            $intervalData[$device] = $payload;
                        }
                    }

                    return [
                        'interval'  => max(1, $interval),
                        // 'timestamp' => $group->first()->timestamp,
                        'timestamp' => \Carbon\Carbon::parse($group->first()->timestamp)->format('H:i:s - d/m'),
                        'data'      => $intervalData,
                        'average_grain_moisture' => !empty($moistures)
                            ? number_format(round(array_sum($moistures) / count($moistures), 2), 2, '.', '')
                            : null,
                    ];
                });

            // Pagination / latest slice
            $offset    = ($page - 1) * $perPage;
            $paginated = $latest ? $formatted->take(10) : $formatted->slice($offset, $perPage);

            $response = [
                'success' => true,
                'data' => [
                    'process_id'  => $process->process_id,
                    'grain_type'  => optional($process->grainType)->nama_jenis ?? 'Unknown',
                    'sensor_data' => $paginated->values(),
                ],
            ];

            if (!$latest) {
                $response['pagination'] = [
                    'current_page' => $page,
                    'last_page'    => (int) ceil($totalRecords / max(1, $perPage)),
                    'per_page'     => $perPage,
                    'total'        => $totalRecords,
                ];
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail proses: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatDuration($minutes)
    {
        if ($minutes === null) return 'N/A';
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $hours > 0 ? "$hours Jam $remainingMinutes Menit" : "$remainingMinutes Menit";
    }

    public function validateProcess(Request $request)
    {
        try {
            // pastikan user login
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // validasi input
            $validated = $request->validate([
                'process_id'        => 'required|integer',
                'berat_gabah_akhir' => 'required|numeric|min:0.01',
            ]);

            $processId = (int) $validated['process_id'];
            $finalWeight = (float) $validated['berat_gabah_akhir'];

            // ambil proses
            $process = DryingProcess::with('bedDryer')->where('process_id', $processId)->first();
            if (!$process) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proses tidak ditemukan.',
                ], 404);
            }

            // pastikan dryer milik user
            if ($process->dryer_id) {
                $ownsDryer = BedDryer::where('dryer_id', $process->dryer_id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$ownsDryer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak berhak memvalidasi proses ini.',
                    ], 403);
                }
            }

            // kalau sudah completed, jangan divalidasi lagi
            // if (strtolower($process->status) === 'completed') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Proses sudah berstatus completed.',
            //     ], 422);
            // }

            // validasi logis: finalWeight harus < berat_gabah_awal (jika ada)
            if (!is_null($process->berat_gabah_awal) && $finalWeight >= (float) $process->berat_gabah_awal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Massa akhir harus lebih kecil dari massa awal.',
                ], 422);
            }

            // update nilai akhir
            $now = Carbon::now();
            // $process->timestamp_selesai = $now;
            $process->berat_gabah_akhir = $finalWeight;

            // hitung durasi aktual (menit)
            // if (!empty($process->timestamp_mulai)) {
            //     $process->durasi_aktual = Carbon::parse($process->timestamp_mulai)->diffInMinutes($now);
            //     // opsional: isi juga durasi_terlaksana kalau kamu ingin
            //     if (empty($process->durasi_terlaksana)) {
            //         $process->durasi_terlaksana = $process->durasi_aktual;
            //     }
            // }

            $process->status = 'completed';
            $process->save();

            return response()->json([
                'success' => true,
                'message' => 'Validasi berhasil.',
                'data' => [
                    'process_id'         => $process->process_id,
                    // 'timestamp_selesai'  => $process->timestamp_selesai ? Carbon::parse($process->timestamp_selesai)->toDateTimeString() : null,
                    // 'durasi_aktual'      => $process->durasi_aktual,
                    // 'durasi_aktual_text' => $process->durasi_aktual ? $this->formatDuration($process->durasi_aktual) : null,
                    'berat_gabah_akhir'  => $process->berat_gabah_akhir,
                    'status'             => $process->status,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memvalidasi: ' . $e->getMessage(),
            ], 500);
        }
    }

}
