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
                            'GrainTemperature' => (float) $measurement->grain_temperature,
                            'GrainMoisture' => (float) $measurement->grain_moisture,
                            'RoomTemperature' => (float) $measurement->room_temperature,
                            'CombustionTemperature' => (float) $measurement->combustion_temperature,
                            'Weight' => (float) $measurement->weight
                        ];
                    })->toArray(),
                    'DryingTime' => (float) $group->drying_time
                ];
            })->toArray();

            return response()->json($formattedData);
        } catch (\Exception $e) {
            Log::error('Error fetching training data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch training data'], 500);
        }
    }
}
