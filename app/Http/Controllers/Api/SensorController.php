<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\DryingProcess;
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
                AVG(suhu_ruangan) as avg_suhu_ruangan
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
                AVG(suhu_ruangan) as avg_suhu_ruangan
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
                    'proses_pengeringan' => $item->dryingProcess->name ?? '-',
                    // 'timestamp' => $item->timestamp,
                    'timestamp' => \Carbon\Carbon::parse($item->timestamp)
                        ->timezone('Asia/Jakarta')
                        ->format('Y-m-d H:i:s'),

                    'kadar_air_gabah' => $item->kadar_air_gabah ?? '-',
                    'suhu_gabah' => $item->suhu_gabah ?? '-',
                    'suhu_ruangan' => $item->suhu_ruangan ?? '-',
                ];
            }),
            'averages' => [
                'avg_kadar_air_gabah' => round($averages->avg_kadar_air_gabah, 2) ?? 0,
                'avg_suhu_gabah' => round($averages->avg_suhu_gabah, 2) ?? 0,
                'avg_suhu_ruangan' => round($averages->avg_suhu_ruangan, 2) ?? 0,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:sensor_devices,device_id',
            'device_type' => 'required|in:grain_sensor,room_sensor',
            'kadar_air_gabah' => 'nullable|numeric|required_if:device_type,grain_sensor',
            'suhu_gabah' => 'nullable|numeric|required_if:device_type,grain_sensor',
            'suhu_ruangan' => 'nullable|numeric|required_if:device_type,room_sensor',
            'timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'device_id' => $request->device_id,
            'timestamp' => $request->timestamp,
        ];

        if ($request->device_type === 'grain_sensor') {
            $data['kadar_air_gabah'] = $request->kadar_air_gabah;
            $data['suhu_gabah'] = $request->suhu_gabah;
        } else {
            $data['suhu_ruangan'] = $request->suhu_ruangan;
        }

        $sensorData = SensorData::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Data sensor berhasil ditambahkan',
            'data' => $sensorData
        ], 201);
    }

    public function getLatestSensorData(Request $request)
    {
        try {
            $ongoingProcess = DryingProcess::where('status', 'ongoing')
                ->orderByDesc('timestamp_mulai')
                ->first();

            $processes = DryingProcess::orderBy('timestamp_mulai', 'desc')->get();

            $activeProcess = null;

            if ($request->filled('process_id')) {
                $activeProcess = DryingProcess::where('process_id', $request->process_id)->first();
            } elseif ($ongoingProcess) {
                $activeProcess = $ongoingProcess;
            }

            $sensorQuery = SensorData::with('sensorDevice');

            if ($request->filled('device_id')) {
                $sensorQuery->where('device_id', $request->device_id);
            }

            // Jika process aktif (entah ongoing atau dikirim lewat request)
            if ($activeProcess) {
                $startTime = $activeProcess->timestamp_mulai;
                $endTime = $activeProcess->timestamp_selesai ?? now();

                // Cek apakah sudah ada sensor yang terkait process_id ini
                $existingSensors = SensorData::where('process_id', $activeProcess->process_id)->exists();

                // Kalau belum ada yang terkait, maka assign data sensor berdasarkan timestamp
                if (!$existingSensors) {
                    $unlinkedSensors = SensorData::whereNull('process_id')
                        ->whereBetween('timestamp', [$startTime, $endTime])
                        ->when($request->filled('device_id'), function ($query) use ($request) {
                            $query->where('device_id', $request->device_id);
                        })
                        ->get();

                    foreach ($unlinkedSensors as $sensor) {
                        $sensor->process_id = $activeProcess->process_id;
                        $sensor->save();
                    }
                }

                // Ambil ulang data sensor dengan process_id ini
                $sensorQuery->where('process_id', $activeProcess->process_id);
            }

            // Ambil data sensor (sudah pasti terkait dengan process aktif)
            $sensorDataList = $sensorQuery->orderBy('timestamp', 'desc')->get();

            $sensorArray = $sensorDataList->map(function ($s) {
                return [
                    'device_name' => $s->sensorDevice->device_name ?? null,
                    'device_type' => $s->sensorDevice->device_type ?? null,
                    'suhu_gabah' => round($s->suhu_gabah, 2),
                    'kadar_air_gabah' => round($s->kadar_air_gabah, 2),
                    'suhu_ruangan' => round($s->suhu_ruangan, 2),
                    'timestamp' => Carbon::parse($s->timestamp)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'process_id' => $s->process_id
                ];
            });

            $avgGrainTemp = round($sensorDataList->avg('suhu_gabah'), 2);
            $avgMoisture = round($sensorDataList->avg('kadar_air_gabah'), 2);
            $avgRoomTemp = round($sensorDataList->avg('suhu_ruangan'), 2);
            $latestTimestamp = $sensorDataList->max('timestamp')
                ? Carbon::parse($sensorDataList->max('timestamp'))->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                : null;

            $sensors = [
                'avg_grain_temperature' => $avgGrainTemp,
                'avg_grain_moisture' => $avgMoisture,
                'avg_room_temperature' => $avgRoomTemp,
                'latest_timestamp' => $latestTimestamp,
                'target_moisture_achieved' => $activeProcess ? $avgMoisture <= $activeProcess->kadar_air_target : false,
                'data' => $sensorArray
            ];

            $dryingProcess = $activeProcess ? [
                'drying_process' => [
                    'process_id' => $activeProcess->process_id,
                    'grain_type_id' => $activeProcess->grain_type_id,
                    'berat_gabah' => $activeProcess->berat_gabah,
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
                'message' => 'Tidak ada proses pengeringan aktif. Menampilkan semua data sensor tersedia.'
            ];

            $allProcesses = $processes->map(function ($p) {
                return [
                    'process_id' => $p->process_id,
                    'user_id' => $p->user_id,
                    'status' => $p->status,
                    'started_at' => Carbon::parse($p->timestamp_mulai)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
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
