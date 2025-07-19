<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\PredictionEstimation;
use Illuminate\Support\Facades\Validator;

class PredictionController extends Controller
{
    public function savePrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|exists:drying_process,process_id',
            'estimasi_durasi' => 'required|numeric',
            'timestamp' => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $prediction = PredictionEstimation::create([
            'process_id' => $request->process_id,
            'estimasi_durasi' => $request->estimasi_durasi,
            'timestamp' => $request->timestamp,
        ]);

        return response()->json(['message' => 'Prediction saved successfully', 'data' => $prediction], 201);
    }

    public function index()
    {
        $predictions = PredictionEstimation::all();
        return response()->json(['estimations' => $predictions], 200);
    }
}