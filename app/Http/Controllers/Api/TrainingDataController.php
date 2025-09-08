<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingGroup;
use App\Models\TrainingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingDataController extends Controller
{
    public function index()
    {
        try {
            $groups = TrainingGroup::with('measurements')->get();
            $formattedData = $groups->map(function ($group) {
                return [
                    'measurements' => $group->measurements->map(function ($measurement) {
                        return [
                            'JenisGabahId' => (int) $measurement->jenis_gabah_id,
                            'KadarAirGabah' => (float) $measurement->kadar_air_gabah,
                            'SuhuGabah' => (float) $measurement->suhu_gabah,
                            'SuhuRuangan' => (float) $measurement->suhu_ruangan,
                            'SuhuPembakaran' => (float) $measurement->suhu_pembakaran,
                            'MassaGabah' => (float) $measurement->massa_gabah,
                            'StatusPengaduk' => (bool) $measurement->status_pengaduk,
                        ];
                    })->toArray(),
                    'EstimasiMenit' => (int) $group->drying_time
                ];
            })->toArray();

            return response()->json($formattedData);
        } catch (\Exception $e) {
            Log::error('Error fetching training data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch training data'], 500);
        }
    }
}