<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\DryingProcess;
use App\Models\SensorDevice;
use App\Models\GrainType;
use App\Models\BedDryer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealtimeDataController extends Controller
{
    /**
     * GET /api/realtime-data?dryer_id=XX
     * Kembalikan status realtime untuk proses (pending/ongoing) dari dryer yang dipilih.
     */
    public function index(Request $request)
    {
        try {
            $dryerId = (int) $request->query('dryer_id');
            if (!$dryerId || !BedDryer::where('dryer_id', $dryerId)->exists()) {
                return response()->json(['error' => 'Parameter dryer_id tidak valid.'], 422);
            }

            // Ambil proses aktif (ongoing/pending) untuk dryer ini
            $dryingProcess = DryingProcess::where('dryer_id', $dryerId)
                ->whereIn('status', ['ongoing', 'pending'])
                ->select(
                    'process_id',
                    'grain_type_id',
                    'berat_gabah_awal',
                    'kadar_air_target',
                    'kadar_air_awal',
                    'kadar_air_akhir',
                    'durasi_rekomendasi',
                    'durasi_terlaksana',
                    'avg_estimasi_durasi',
                    'status',
                    'created_at as started_at',
                    'timestamp_selesai'
                )
                ->orderBy('created_at', 'desc')
                ->first();

            Log::info('Drying process found for dryer '.$dryerId.': '.($dryingProcess ? '1' : '0'));

            $sensors = [];
            $initialSensors = [];
            $avgSensors = [];
            $avgInitialSensors = [];
            $nama_jenis = null;

            if ($dryingProcess) {
                // Nama jenis gabah (jika ada)
                $grainType = $dryingProcess->grain_type_id
                    ? GrainType::where('grain_type_id', $dryingProcess->grain_type_id)->select('nama_jenis')->first()
                    : null;
                $nama_jenis = $grainType?->nama_jenis;

                // Data sensor terbaru per device
                $latestSensors = SensorData::select(
                        'device_id',
                        'suhu_gabah as grain_temperature',
                        'kadar_air_gabah as grain_moisture',
                        'suhu_ruangan as room_temperature',
                        'suhu_pembakaran as burning_temperature',
                        'status_pengaduk as stirrer_status',
                        'created_at as timestamp'
                    )
                    ->where('process_id', $dryingProcess->process_id)
                    ->whereIn('sensor_id', function ($q) use ($dryingProcess) {
                        $q->select(DB::raw('MAX(sensor_id)'))
                            ->from('sensor_data')
                            ->where('process_id', $dryingProcess->process_id)
                            ->groupBy('device_id');
                    })
                    ->get();

                $sensors = $latestSensors->map(function ($sensor) {
                    $device = SensorDevice::where('device_id', $sensor->device_id)->first();
                    return [
                        'device_id' => $sensor->device_id,
                        'device_name' => $device ? $device->device_name : 'Unknown',
                        'grain_temperature'   => $sensor->grain_temperature !== null ? number_format((float)$sensor->grain_temperature, 2, '.', '') : null,
                        'grain_moisture'      => $sensor->grain_moisture !== null ? number_format((float)$sensor->grain_moisture, 2, '.', '') : null,
                        'room_temperature'    => $sensor->room_temperature !== null ? number_format((float)$sensor->room_temperature, 2, '.', '') : null,
                        'burning_temperature' => $sensor->burning_temperature !== null ? number_format((float)$sensor->burning_temperature, 2, '.', '') : null,
                        'stirrer_status'      => $sensor->stirrer_status !== null ? (bool)$sensor->stirrer_status : null,
                        'timestamp'           => Carbon::parse($sensor->timestamp)->toIso8601String(),
                    ];
                })->toArray();

                $avgSensors = $this->calculateAverages($sensors);

                // Data sensor awal per device
                $initialSensorsQuery = SensorData::select(
                        'device_id',
                        'suhu_gabah as grain_temperature',
                        'kadar_air_gabah as grain_moisture',
                        'suhu_ruangan as room_temperature',
                        'suhu_pembakaran as burning_temperature',
                        'status_pengaduk as stirrer_status',
                        'created_at as timestamp'
                    )
                    ->where('process_id', $dryingProcess->process_id)
                    ->whereIn('sensor_id', function ($q) use ($dryingProcess) {
                        $q->select(DB::raw('MIN(sensor_id)'))
                            ->from('sensor_data')
                            ->where('process_id', $dryingProcess->process_id)
                            ->groupBy('device_id');
                    })
                    ->get();

                $initialSensors = $initialSensorsQuery->map(function ($sensor) {
                    $device = SensorDevice::where('device_id', $sensor->device_id)->first();
                    return [
                        'device_id' => $sensor->device_id,
                        'device_name' => $device ? $device->device_name : 'Unknown',
                        'grain_temperature'   => $sensor->grain_temperature !== null ? number_format((float)$sensor->grain_temperature, 2, '.', '') : null,
                        'grain_moisture'      => $sensor->grain_moisture !== null ? number_format((float)$sensor->grain_moisture, 2, '.', '') : null,
                        'room_temperature'    => $sensor->room_temperature !== null ? number_format((float)$sensor->room_temperature, 2, '.', '') : null,
                        'burning_temperature' => $sensor->burning_temperature !== null ? number_format((float)$sensor->burning_temperature, 2, '.', '') : null,
                        'stirrer_status'      => $sensor->stirrer_status !== null ? (bool)$sensor->stirrer_status : null,
                        'timestamp'           => Carbon::parse($sensor->timestamp)->toIso8601String(),
                    ];
                })->toArray();

                $avgInitialSensors = $this->calculateAverages($initialSensors);
            }

            $response = [
                'now_sensors'      => array_merge(['data' => $sensors], $avgSensors),
                'initial_sensors'  => array_merge(['data' => $initialSensors], $avgInitialSensors),
                'drying_process'   => $dryingProcess ? [
                    'process_id'          => $dryingProcess->process_id,
                    'grain_type_id'       => $dryingProcess->grain_type_id,
                    'nama_jenis'          => $nama_jenis,
                    'berat_gabah_awal'    => $dryingProcess->berat_gabah_awal,
                    'kadar_air_target'    => $dryingProcess->kadar_air_target,
                    'kadar_air_awal'      => $dryingProcess->kadar_air_awal,
                    'kadar_air_akhir'     => $dryingProcess->kadar_air_akhir !== null ? (float)$dryingProcess->kadar_air_akhir : null,
                    'durasi_rekomendasi'  => $dryingProcess->durasi_rekomendasi,
                    'avg_estimasi_durasi' => $dryingProcess->avg_estimasi_durasi !== null ? round($dryingProcess->avg_estimasi_durasi, 0) : null,
                    'durasi_terlaksana'   => $dryingProcess->durasi_terlaksana,
                    'status'              => $dryingProcess->status,
                    'started_at'          => Carbon::parse($dryingProcess->started_at)->toIso8601String(),
                ] : null,
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error fetching realtime data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch realtime data'], 500);
        }
    }

    /**
     * Rata-rata 3 parameter.
     */
    private function calculateAverages(array $sensors): array
    {
        $grainTemperatures = [];
        $grainMoistures    = [];
        $roomTemperatures  = [];

        foreach ($sensors as $s) {
            if ($s['grain_temperature'] !== null) $grainTemperatures[] = (float)$s['grain_temperature'];
            if ($s['grain_moisture']    !== null) $grainMoistures[]    = (float)$s['grain_moisture'];
            if ($s['room_temperature']  !== null) $roomTemperatures[]  = (float)$s['room_temperature'];
        }

        $avg = [];
        if ($grainTemperatures) $avg['averageGrainTemperature'] = number_format(array_sum($grainTemperatures)/count($grainTemperatures), 2, '.', '');
        if ($grainMoistures)    $avg['averageGrainMoisture']    = number_format(array_sum($grainMoistures)/count($grainMoistures), 2, '.', '');
        if ($roomTemperatures)  $avg['averageRoomTemperature']  = number_format(array_sum($roomTemperatures)/count($roomTemperatures), 2, '.', '');
        return $avg;
    }

    /**
     * GET /api/dashboard-data?dryer_id=XX
     * Dashboard singkat berdasarkan proses ongoing dryer yang dipilih.
     */
    public function dashboardData(Request $request)
    {
        try {
            $dryerId = (int) $request->query('dryer_id');
            if (!$dryerId || !BedDryer::where('dryer_id', $dryerId)->exists()) {
                return response()->json(['error' => 'Parameter dryer_id tidak valid.'], 422);
            }

            $dryingProcess = DryingProcess::where('dryer_id', $dryerId)
                ->where('status', 'ongoing')
                ->select('process_id','durasi_rekomendasi','durasi_terlaksana','timestamp_mulai','kadar_air_akhir')
                ->orderBy('created_at', 'desc')
                ->first();

            Log::info('Dashboard process for dryer '.$dryerId.': '.($dryingProcess ? '1' : '0'));

            $response = [
                'current_moisture'        => null,
                'estimated_duration'      => null,
                'estimated_finish'        => null,
                'is_moisture_warning'     => false,
                'moisture_data'           => [],
                'grain_temperature_data'  => [],
                'room_temperature_data'   => [],
                'burning_temperature_data'=> [],
            ];

            if ($dryingProcess) {
                // Moisture sekarang
                $latestSensor = SensorData::where('process_id', $dryingProcess->process_id)
                    ->select('kadar_air_gabah','suhu_gabah','created_at')
                    ->orderBy('created_at','desc')
                    ->first();

                $response['current_moisture'] = $latestSensor && $latestSensor->kadar_air_gabah !== null
                    ? number_format((float)$latestSensor->kadar_air_gabah, 2, '.', '')
                    : ($dryingProcess->kadar_air_akhir !== null
                        ? number_format((float)$dryingProcess->kadar_air_akhir, 2, '.', '')
                        : null);

                if ($response['current_moisture'] !== null) {
                    $cm = (float)$response['current_moisture'];
                    $response['is_moisture_warning'] = ($cm < 14.0 || $cm > 29.0);
                }

                // Estimasi durasi
                if ($dryingProcess->durasi_rekomendasi !== null) {
                    $h = floor($dryingProcess->durasi_rekomendasi / 60);
                    $m = $dryingProcess->durasi_rekomendasi % 60;
                    $response['estimated_duration'] = sprintf('%d jam %d menit', $h, $m);
                }

                // Estimasi selesai
                if ($dryingProcess->timestamp_mulai && $dryingProcess->durasi_rekomendasi !== null) {
                    $finish = Carbon::parse($dryingProcess->timestamp_mulai)->addMinutes($dryingProcess->durasi_rekomendasi);
                    $response['estimated_finish'] = $finish->isSameDay(Carbon::now())
                        ? $finish->format('H:i')
                        : $finish->format('H:i d F');
                }

                // Ambil 5 titik terakhir (per timestamp) untuk grafik
                $sensorRows = SensorData::where('process_id', $dryingProcess->process_id)
                    ->select('kadar_air_gabah','suhu_gabah','suhu_ruangan','suhu_pembakaran','created_at')
                    ->where(function ($q) {
                        $q->whereNotNull('kadar_air_gabah')
                          ->orWhereNotNull('suhu_gabah')
                          ->orWhereNotNull('suhu_ruangan')
                          ->orWhereNotNull('suhu_pembakaran');
                    })
                    ->orderBy('created_at','desc')
                    ->take(50)
                    ->get()
                    ->groupBy(fn($i) => $i->created_at)
                    ->take(5)
                    ->sortByDesc(fn($g) => strtotime($g->first()->created_at))
                    ->values();

                $moisture = $grainT = $roomT = $burnT = [];

                foreach ($sensorRows as $group) {
                    $ts = $group->first()->created_at;
                    $label = date('i:s', strtotime($ts));

                    $mSum = $mCnt = $gSum = $gCnt = $rSum = $rCnt = $bSum = $bCnt = 0;

                    foreach ($group as $row) {
                        if (!is_null($row->kadar_air_gabah)) { $mSum += (float)$row->kadar_air_gabah; $mCnt++; }
                        if (!is_null($row->suhu_gabah))      { $gSum += (float)$row->suhu_gabah;      $gCnt++; }
                        if (!is_null($row->suhu_ruangan))    { $rSum += (float)$row->suhu_ruangan;    $rCnt++; }
                        if (!is_null($row->suhu_pembakaran)) { $bSum += (float)$row->suhu_pembakaran; $bCnt++; }
                    }

                    if ($mCnt) $moisture[] = ['time' => $label, 'data' => number_format($mSum / $mCnt, 2, '.', '')];
                    if ($gCnt) $grainT[]   = ['time' => $label, 'data' => number_format($gSum / $gCnt, 2, '.', '')];
                    if ($rCnt) $roomT[]    = ['time' => $label, 'data' => number_format($rSum / $rCnt, 2, '.', '')];
                    if ($bCnt) $burnT[]    = ['time' => $label, 'data' => number_format($bSum / $bCnt, 2, '.', '')];
                }

                $response['moisture_data']            = $moisture;
                $response['grain_temperature_data']   = $grainT;
                $response['room_temperature_data']    = $roomT;
                $response['burning_temperature_data'] = $burnT;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::info('Error fetching dashboard data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch dashboard data'], 500);
        }
    }
}
