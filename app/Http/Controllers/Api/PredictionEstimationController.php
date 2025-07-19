<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\PredictionEstimation;

class PredictionEstimationController extends Controller
{
    public function index($process_id)
    {
        $estimations = PredictionEstimation::where('process_id', $process_id)->get();
        return response()->json(['estimations' => $estimations], 200);
    }
}