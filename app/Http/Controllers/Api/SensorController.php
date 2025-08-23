<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\DryerProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SensorController extends Controller
{
    public function getByDevice(Request $request)
{
    $deviceId = $request->query('device_id');

    if ($deviceId) {
        // Ambil semua data sensor untuk perangkat tertentu
        $query = SensorData::with(['dryingProcess', 'sensorDevice'])
            ->where('device_id', $deviceId)
            ->orderBy('timestamp', 'desc');

        $sensorData = $query->get();

        // Hitung rata-rata untuk perangkat tertentu
        $averages = SensorData::selectRaw('
            AVG(kadar_air_gabah) as avg_kadar_air_gabah,
            AVG(suhu_gabah) as avg_suhu_gabah,
            AVG(suhu_ruangan) as avg_suhu_ruangan,
            AVG(suhu_pembakaran) as avg_suhu_pembakaran
        ')
            ->where('device_id', $deviceId)
            ->first();
    } else {
        // Ambil data sensor terakhir untuk setiap perangkat
        $sensorData = SensorData::with(['dryingProcess', 'sensorDevice'])
            ->select('sensor_data.*')
            ->whereIn('sensor_data.sensor_id', function ($query) {
                $query->selectRaw('MAX(sensor_id)')
                    ->from('sensor_data')
                    ->groupBy('device_id');
            })
            ->orderBy('timestamp', 'desc')
            ->get();

        // Hitung rata-rata dari data sensor terakhir
        $averages = SensorData::selectRaw('
            AVG(kadar_air_gabah) as avg_kadar_air_gabah,
            AVG(suhu_gabah) as avg_suhu_gabah,
            AVG(suhu_ruangan) as avg_suhu_ruangan,
            AVG(suhu_pembakaran) as avg_suhu_pembakaran
        ')
            ->whereIn('sensor_id', function ($query) {
                $query->selectRaw('MAX(sensor_id)')
                    ->from('sensor_data')
                    ->groupBy('device_id');
            })
            ->first();
    }

    return response()->json([
        'message' => 'Data sensor berhasil diambil',
        'data' => $sensorData->map(function ($item) {
            return [
                'device_id' => $item->device_id,
                'proses_pengeringan' => $item->dryingProcess->name ?? '-',
                'timestamp' => \Carbon\Carbon::parse($item->timestamp)
                    ->timezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s'),
                'kadar_air_gabah' => $item->kadar_air_gabah ?? '-',
                'suhu_gabah' => $item->suhu_gabah ?? '-',
                'suhu_ruangan' => $item->suhu_ruangan ?? '-',
                'suhu_pembakaran' => $item->suhu_pembakaran ?? '-',
            ];
        }),
        'averages' => [
            'device_id' => $deviceId ?? 'all',
            'avg_kadar_air_gabah' => round($averages->avg_kadar_air_gabah, 2) ?? 0,
            'avg_suhu_gabah' => round($averages->avg_suhu_gabah, 2) ?? 0,
            'avg_suhu_ruangan' => round($averages->avg_suhu_ruangan, 2) ?? 0,
            'avg_suhu_pembakaran' => round($averages->avg_suhu_pembakaran, 2) ?? 0,
        ]
    ]);
}


    public function store(Request $request)
    {
        // Validasi data yang diterima
        $validator = Validator::make($request->all(), [
            'sensor' => 'required|array',
            'sensor.*.device_id' => 'required|exists:sensor_devices,device_id',
            'sensor.*.timestamp' => 'required|date',
            'sensor.*.kadar_air_gabah' => 'nullable|numeric|min:10|max:30',
            'sensor.*.suhu_gabah' => 'nullable|numeric|min:30|max:50',
            'sensor.*.suhu_ruangan' => 'nullable|numeric|min:25|max:40',
            'sensor.*.suhu_pembakaran' => 'nullable|numeric|min:100|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $sensorDataList = [];
        foreach ($request->sensor as $sensor) {
            // Simpan data sensor ke database
            $sensorData = SensorData::create([
                'device_id' => $sensor['device_id'],
                'timestamp' => $sensor['timestamp'],
                'kadar_air_gabah' => $sensor['kadar_air_gabah'] ?? null,
                'suhu_gabah' => $sensor['suhu_gabah'] ?? null,
                'suhu_ruangan' => $sensor['suhu_ruangan'] ?? null,
                'suhu_pembakaran' => $sensor['suhu_pembakaran'] ?? null,
            ]);
            $sensorDataList[] = $sensorData;
        }

        return response()->json([
            'status' => true,
            'message' => 'Data sensor berhasil ditambahkan',
            'data' => $sensorDataList
        ], 201);
    }

    public function getLatestSensorData(Request $request)
    {
        try {
            if ($request->filled('process_id')) {
                $activeProcess = DryerProcess::where('process_id', $request->process_id)
                    ->where('user_id', $request->user()->id) // filter sesuai user login
                    ->first();
            } else {
                $activeProcess = DryerProcess::where('status', 'ongoing')
                    ->where('user_id', $request->user()->id) // filter sesuai user login
                    ->orderByDesc('timestamp_mulai')
                    ->first();
            }

            $processes = DryerProcess::orderBy('timestamp_mulai', 'desc')->get();

            $sensorQuery = SensorData::with('sensorDevice');

            if ($request->filled('device_id')) {
                $sensorQuery->where('device_id', $request->device_id);
            }

            // Jika ada proses aktif
            if ($activeProcess) {
                $startTime = $activeProcess->timestamp_mulai;
                $endTime = ($activeProcess->status === 'ongoing') ? now() : $activeProcess->timestamp_selesai;

                // Abaikan timestamp_selesai jika belum selesai
                if ($activeProcess->status !== 'completed') {
                    $activeProcess->timestamp_selesai = null;
                }

                // Ambil sensor yang belum di-assign ke proses ini
                $unlinkedSensors = SensorData::whereNull('process_id')
                    ->whereBetween('timestamp', [$startTime, $endTime])
                    ->when($request->filled('device_id'), function ($query) use ($request) {
                        return $query->where('device_id', $request->device_id);
                    })
                    ->get();

                foreach ($unlinkedSensors as $sensor) {
                    $sensor->process_id = $activeProcess->process_id;
                    $sensor->save();
                }

                // Pastikan ambil sensor yang sudah dikaitkan ke proses aktif
                $sensorQuery->where('process_id', $activeProcess->process_id);
            }

            // Ambil data sensor terbaru
            $sensorDataList = $sensorQuery->orderBy('timestamp', 'desc')->get();

            // Mapping ke array
            $sensorArray = $sensorDataList->map(function ($s) {
                return [
                    'device_name' => $s->sensorDevice->device_name ?? null,
                    'device_type' => $s->sensorDevice->device_type ?? null,
                    'suhu_gabah' => round($s->suhu_gabah, 2),
                    'kadar_air_gabah' => round($s->kadar_air_gabah, 2),
                    'suhu_ruangan' => round($s->suhu_ruangan, 2),
                    'suhu_pembakaran' => round($s->suhu_pembakaran, 2),
                    'status_pengaduk' => is_null($s->status_pengaduk) ? null : (bool) $s->status_pengaduk, // 🔹 status pengaduk
                    'timestamp' => Carbon::parse($s->timestamp)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'process_id' => $s->process_id
                ];
            });

            $avgGrainTemp = round($sensorDataList->avg('suhu_gabah'), 2);
            $avgMoisture = round($sensorDataList->avg('kadar_air_gabah'), 2);
            $avgRoomTemp = round($sensorDataList->avg('suhu_ruangan'), 2);
            $avgBurnTemp = round($sensorDataList->avg('suhu_pembakaran'), 2);
            $latestTimestamp = $sensorDataList->max('timestamp')
                ? Carbon::parse($sensorDataList->max('timestamp'))->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                : null;

            $targetAchieved = $activeProcess ? $avgMoisture <= $activeProcess->kadar_air_target : false;

            // Jika target tercapai, update status (opsional, atau lakukan via endpoint stop manual)
            if ($targetAchieved && $activeProcess && $activeProcess->status === 'ongoing') {
                // Kamu bisa update status secara otomatis di sini jika mau
                // $activeProcess->status = 'completed';
                // $activeProcess->timestamp_selesai = now();
                // $activeProcess->save();
            }

            $latestStirrerStatus = $sensorDataList->whereNotNull('status_pengaduk')->first()
            ? (bool) $sensorDataList->sortByDesc('timestamp')->first()->status_pengaduk
            : null;

        $sensors = [
            'avg_grain_temperature' => $avgGrainTemp,
            'avg_grain_moisture' => $avgMoisture,
            'avg_room_temperature' => $avgRoomTemp,
            'avg_combustion_temperature' => $avgBurnTemp,
            'latest_stirrer_status' => $latestStirrerStatus, // 🔹 status pengaduk terakhir
            'latest_timestamp' => $latestTimestamp,
            'target_moisture_achieved' => $targetAchieved,
            'data' => $sensorArray
        ];

            $dryingProcess = $activeProcess ? [
                'drying_process' => [
                    'process_id' => $activeProcess->process_id,
                    'grain_type_id' => $activeProcess->grain_type_id,
                    'berat_gabah_awal' => $activeProcess->berat_gabah_awal,
                    'kadar_air_target' => $activeProcess->kadar_air_target,
                    'status' => $activeProcess->status,
                    'durasi_rekomendasi' => $activeProcess->durasi_rekomendasi,
                    'started_at' => Carbon::parse($activeProcess->timestamp_mulai)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'finished_at' => $activeProcess->timestamp_selesai
                        ? Carbon::parse($activeProcess->timestamp_selesai)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                        : null,
                ]
            ] : [
                'drying_process' => null,
                'message' => 'Tidak ada proses pengeringan aktif.'
            ];

            $allProcesses = $processes
            ->where('user_id', $request->user()->id)
            ->map(function ($p) {
                return [
                    'process_id' => $p->process_id,
                    'user_id' => $p->user_id,
                    'status' => $p->status,
                    'started_at' => Carbon::parse($p->timestamp_mulai)
                        ->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kadar_air_target' => $p->kadar_air_target
                ];
            });

            return response()->json(array_merge([
                'sensors' => $sensors,
                'data' => $sensorArray,
                'summary' => $sensors,
                'all_processes' => $allProcesses
            ], $dryingProcess), 200);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data sensor: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data sensor'], 500);
        }
    }
}
