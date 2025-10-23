<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileTrainingDataController extends Controller
{
    public function index(): JsonResponse
    {
        set_time_limit(0);

        try {
            $groups = DB::table('training_group')
                ->orderBy('group_id')
                ->cursor();

            $result = [];

            foreach ($groups as $group) {
                $rows = DB::table('training_data')
                    ->where('group_id', $group->group_id)
                    ->orderBy('timestamp')
                    ->get();

                $intervals = [];
                $i = 1;

                foreach ($rows as $r) {
                    $suhu_gabah       = $this->fmt7($r->suhu_gabah);
                    $kadar_air_gabah  = $this->fmt7($r->kadar_air_gabah);
                    $suhu_ruangan     = $this->fmt7($r->suhu_ruangan);
                    $suhu_pembakaran  = $this->fmt7($r->suhu_pembakaran);
                    $estimasi_durasi  = $this->fmt7($r->durasi_aktual);

                    $intervals[] = [
                        'interval_id'     => $i++,
                        'timestamp'       => $r->timestamp,
                        'estimasi_durasi' => $estimasi_durasi,
                        'sensor_data'     => [
                            [
                                'suhu_gabah'       => $suhu_gabah,
                                'kadar_air_gabah'  => $kadar_air_gabah,
                                'suhu_ruangan'     => $suhu_ruangan,
                                'suhu_pembakaran'  => $suhu_pembakaran,
                                'status_pengaduk'  => (bool) $r->status_pengaduk,
                            ]
                        ],
                    ];
                }

                $result[] = [
                    'process_id'          => $group->group_id,
                    'grain_type_id'       => $group->grain_type_id,
                    'berat_gabah'         => $group->massa_awal,
                    // 'avg_estimasi_durasi' => null,
                    'intervals'           => $intervals,
                ];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error fetching training training_data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    private function fmt7($v)
    {
        return is_null($v) ? null : number_format((float)$v, 7, '.', '');
    }
}
