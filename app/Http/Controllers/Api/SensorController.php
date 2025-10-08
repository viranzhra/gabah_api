<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\SensorDevice;
use App\Models\DryerProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\SensorDataUpdated;

class SensorController extends Controller
{
    public function getByDevice(Request $request)
    {
        $deviceId = $request->query('device_id');

        if ($deviceId) {
            // Fetch all sensor data for a specific device
            $query = SensorData::with(['dryingProcess', 'dryingProcess.grainType', 'sensorDevice'])
                ->where('device_id', $deviceId)
                ->orderBy('timestamp', 'desc');

            $sensorData = $query->get();

            // Calculate averages for the specific device
            $averages = SensorData::selectRaw('
                AVG(kadar_air_gabah) as avg_kadar_air_gabah,
                AVG(suhu_gabah) as avg_suhu_gabah,
                AVG(suhu_ruangan) as avg_suhu_ruangan,
                AVG(suhu_pembakaran) as avg_suhu_pembakaran
            ')
                ->where('device_id', $deviceId)
                ->first();
        } else {
            // Fetch latest sensor data for each device
            $sensorData = SensorData::with(['dryingProcess', 'dryingProcess.grainType', 'sensorDevice'])
                ->select('sensor_data.*')
                ->whereIn('sensor_data.sensor_id', function ($query) {
                    $query->selectRaw('MAX(sensor_id)')
                        ->from('sensor_data')
                        ->groupBy('device_id');
                })
                ->orderBy('timestamp', 'desc')
                ->get();

            // Calculate averages for latest sensor data
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
                    'proses_pengeringan' => $item->dryingProcess && $item->dryingProcess->grainType
                        ? $item->dryingProcess->grainType->nama_jenis
                        : '-',
                    'timestamp' => Carbon::parse($item->timestamp)
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

    // public function store(Request $request)
    // {
    //     // Validate received data
    //     $validator = Validator::make($request->all(), [
    //         'sensor' => 'required|array',
    //         'sensor.*.device_id' => 'required|exists:sensor_devices,device_id',
    //         'sensor.*.timestamp' => 'required|date',
    //         'sensor.*.kadar_air_gabah' => 'nullable|numeric|min:0|max:100',
    //         'sensor.*.suhu_gabah' => 'nullable|numeric|min:0|max:100',
    //         'sensor.*.suhu_ruangan' => 'nullable|numeric|min:0|max:100',
    //         'sensor.*.suhu_pembakaran' => 'nullable|numeric|min:0|max:200',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $sensorDataList = [];
    //     foreach ($request->sensor as $sensor) {
    //         // Save sensor data to database
    //         $sensorData = SensorData::create([
    //             'device_id' => $sensor['device_id'],
    //             'timestamp' => $sensor['timestamp'],
    //             'kadar_air_gabah' => $sensor['kadar_air_gabah'] ?? null,
    //             'suhu_gabah' => $sensor['suhu_gabah'] ?? null,
    //             'suhu_ruangan' => $sensor['suhu_ruangan'] ?? null,
    //             'suhu_pembakaran' => $sensor['suhu_pembakaran'] ?? null,
    //         ]);
    //         $sensorDataList[] = $sensorData;
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data sensor berhasil ditambahkan',
    //         'data' => $sensorDataList
    //     ], 201);
    // }

    public function store(Request $request)
    {
        Log::info('Store endpoint called with payload:', $request->all());

        // Validasi data yang diterima
        $validator = Validator::make($request->all(), [
            'sensor' => 'required|array',
            'sensor.*.device_id' => 'required|exists:sensor_devices,device_id',
            'sensor.*.timestamp' => 'required|date',
            'sensor.*.kadar_air_gabah' => 'nullable|numeric|min:0|max:100',
            'sensor.*.suhu_gabah' => 'nullable|numeric|min:0|max:100',
            'sensor.*.suhu_ruangan' => 'nullable|numeric|min:0|max:100',
            'sensor.*.suhu_pembakaran' => 'nullable|numeric|min:0|max:200',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for sensor data:', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil mapping device_id ke dryer_id untuk optimasi
        $deviceIds = array_column($request->sensor, 'device_id');
        $dryers = SensorDevice::whereIn('device_id', $deviceIds)
            ->pluck('dryer_id', 'device_id')
            ->toArray();

        $sensorDataList = [];
        try {
            DB::beginTransaction();

            foreach ($request->sensor as $sensor) {
                // Cari proses pengeringan aktif untuk device_id
                $dryerId = $dryers[$sensor['device_id']] ?? null;
                if (!$dryerId) {
                    Log::error('Device ID not found:', ['device_id' => $sensor['device_id']]);
                    return response()->json([
                        'status' => false,
                        'message' => 'Device ID tidak ditemukan: ' . $sensor['device_id']
                    ], 422);
                }

                $process = DryerProcess::where('dryer_id', $dryerId)
                    ->where('status', 'ongoing')
                    ->orderByDesc('timestamp_mulai')
                    ->first();

                // Simpan data sensor ke database
                $sensorData = SensorData::create([
                    'device_id' => $sensor['device_id'],
                    'process_id' => $process ? $process->process_id : null,
                    'timestamp' => $sensor['timestamp'],
                    'kadar_air_gabah' => $sensor['kadar_air_gabah'] ?? null,
                    'suhu_gabah' => $sensor['suhu_gabah'] ?? null,
                    'suhu_ruangan' => $sensor['suhu_ruangan'] ?? null,
                    'suhu_pembakaran' => $sensor['suhu_pembakaran'] ?? null,
                ]);

                $sensorDataList[] = $sensorData;

                // Trigger event broadcast
                event(new SensorDataUpdated($sensorData));
                Log::info('Sensor data stored and broadcasted: ' . json_encode([
                    'sensorData' => $sensorData->toArray(),
                    'channel' => 'drying-process.' . ($sensorData->process_id ?? 'default')
                ]));
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data sensor berhasil ditambahkan dan dibroadcast',
                'data' => $sensorDataList
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing sensor data: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan data sensor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLatestSensorData(Request $request)
{
    try {
        $sensorQuery = SensorData::with(['sensorDevice', 'dryingProcess', 'dryingProcess.grainType']);

        // Prioritize dryer_id if provided
        if ($request->filled('dryer_id')) {
            $activeProcess = DryerProcess::where('dryer_id', $request->dryer_id)
                ->where('status', 'ongoing')
                ->orderByDesc('timestamp_mulai')
                ->first();
        } else {
            $activeProcess = DryerProcess::where('status', 'ongoing')
                ->orderByDesc('timestamp_mulai')
                ->first();
        }

        $processes = DryerProcess::orderBy('timestamp_mulai', 'desc')->get();

        if ($request->filled('device_id')) {
            $sensorQuery->where('device_id', $request->device_id);
        }

        if ($request->filled('dryer_id')) {
            $sensorQuery->whereHas('dryingProcess', function ($query) use ($request) {
                $query->where('dryer_id', $request->dryer_id);
            });
        }

        if ($activeProcess) {
            $startTime = $activeProcess->timestamp_mulai;
            $endTime = ($activeProcess->status === 'ongoing') ? now() : $activeProcess->timestamp_selesai;

            if ($activeProcess->status !== 'completed') {
                $activeProcess->timestamp_selesai = null;
            }

            $unlinkedSensors = SensorData::whereNull('process_id')
                ->whereBetween('timestamp', [$startTime, $endTime])
                ->when($request->filled('device_id'), function ($query) use ($request) {
                    return $query->where('device_id', $request->device_id);
                })
                ->when($request->filled('dryer_id'), function ($query) use ($activeProcess) {
                    return $query->whereHas('sensorDevice', function ($q) use ($activeProcess) {
                        $q->where('dryer_id', $activeProcess->dryer_id);
                    });
                })
                ->get();

            foreach ($unlinkedSensors as $sensor) {
                $sensor->process_id = $activeProcess->process_id;
                $sensor->save();
            }

            $sensorQuery->where('process_id', $activeProcess->process_id);
        }

        // Ambil 10 data terakhir berdasarkan timestamp
        $sensorDataList = $sensorQuery->orderBy('timestamp', 'desc')->take(10)->get();

        $sensorArray = $sensorDataList->map(function ($s) {
            return [
                'sensor_id' => $s->sensor_id, // Tambahkan sensor_id
                'device_id' => $s->device_id, // Tambahkan device_id
                'device_name' => $s->sensorDevice->device_name ?? null,
                'device_type' => $s->sensorDevice->device_type ?? null,
                'suhu_gabah' => round($s->suhu_gabah, 2) ?? null,
                'kadar_air_gabah' => round($s->kadar_air_gabah, 2) ?? null,
                'suhu_ruangan' => round($s->suhu_ruangan, 2) ?? null,
                'suhu_pembakaran' => round($s->suhu_pembakaran, 2) ?? null,
                'status_pengaduk' => is_null($s->status_pengaduk) ? null : (bool) $s->status_pengaduk,
                'timestamp' => Carbon::parse($s->timestamp)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'process_id' => $s->process_id,
            ];
        });

        $avgGrainTemp = round($sensorDataList->avg('suhu_gabah'), 2) ?? null;
        $avgMoisture = round($sensorDataList->avg('kadar_air_gabah'), 2) ?? null;
        $avgRoomTemp = round($sensorDataList->avg('suhu_ruangan'), 2) ?? null;
        $avgBurnTemp = round($sensorDataList->avg('suhu_pembakaran'), 2) ?? null;
        $latestTimestamp = $sensorDataList->max('timestamp')
            ? Carbon::parse($sensorDataList->max('timestamp'))->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            : null;

        $targetAchieved = $activeProcess ? $avgMoisture <= $activeProcess->kadar_air_target : false;

        $latestSensor = $sensorDataList
            ->whereNotNull('status_pengaduk')
            ->sortByDesc('timestamp')
            ->first();

        $latestStirrerStatus = $latestSensor
            ? (bool) $latestSensor->status_pengaduk
            : null;

        $sensors = [
            'avg_grain_temperature' => $avgGrainTemp,
            'avg_grain_moisture' => $avgMoisture,
            'avg_room_temperature' => $avgRoomTemp,
            'avg_combustion_temperature' => $avgBurnTemp,
            'latest_stirrer_status' => $latestStirrerStatus,
            'latest_timestamp' => $latestTimestamp,
            'target_moisture_achieved' => $targetAchieved,
            'data' => $sensorArray,
        ];

        $dryingProcess = $activeProcess ? [
            'drying_process' => [
                'process_id' => $activeProcess->process_id,
                'dryer_id' => $activeProcess->dryer_id,
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

        $allProcesses = $processes->map(function ($p) {
            return [
                'process_id' => $p->process_id,
                'dryer_id' => $p->dryer_id,
                'status' => $p->status,
                'started_at' => Carbon::parse($p->timestamp_mulai)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'kadar_air_target' => $p->kadar_air_target,
            ];
        });

        return response()->json(array_merge([
            'sensors' => $sensors,
            'data' => $sensorArray,
            'summary' => $sensors,
            'all_processes' => $allProcesses,
        ], $dryingProcess), 200);
    } catch (\Exception $e) {
        Log::error('Gagal mengambil data sensor: ' . $e->getMessage());
        return response()->json(['error' => 'Gagal mengambil data sensor'], 500);
    }
}
}