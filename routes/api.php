<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\JenisGabahController;
use App\Http\Controllers\Api\PrediksiController;
use App\Http\Controllers\Api\DryingProcessController;
use App\Http\Controllers\Api\OperatorDryingProcessController;
use App\Http\Controllers\Api\TrainingDataController;
use App\Http\Controllers\Api\PredictionController;

use App\Http\Controllers\Api\DryerProcessController;
use App\Http\Controllers\Api\PredictionEstimationController;

use App\Http\Controllers\Api\TrainingDataImportController;

// Rute publik (tidak memerlukan autentikasi)
Route::post('login', [AuthController::class, 'login'])->name('login'); // Add name('login')

// Rute yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/jenis-gabah', [JenisGabahController::class, 'index']);
    Route::get('/jenis-gabah/{id}', [JenisGabahController::class, 'show']);
    Route::post('/jenis-gabah', [JenisGabahController::class, 'store']);
    Route::put('/jenis-gabah/{id}', [JenisGabahController::class, 'update']);
    Route::delete('/jenis-gabah/{id}', [JenisGabahController::class, 'destroy']);

    Route::post('/prediksi/store', [DryingProcessController::class, 'store']);
    // Route::get('/drying-process', [DryingProcessController::class, 'index']);
    // Route::get('/drying-process', [DryerProcessController::class, 'index']);
    Route::post('/drying-process/{id}/start', [DryingProcessController::class, 'start']);
    Route::post('/drying-process/{id}/complete', [DryingProcessController::class, 'complete']);
    Route::post('/drying-process/{id}/update-duration', [DryingProcessController::class, 'updateDuration']);
    // Route::get('/drying-process/{id}', [DryingProcessController::class, 'show']);

    Route::get('/operator/prediksi', [OperatorDryingProcessController::class, 'index']);
    Route::post('/operator/prediksi/store', [OperatorDryingProcessController::class, 'store']);
    Route::post('/operator/drying-process/{id}/complete', [OperatorDryingProcessController::class, 'complete']);
    Route::post('/operator/drying-process/{id}/update-duration', [OperatorDryingProcessController::class, 'updateDuration']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'edit']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::get('/roles/{id}/permissions', [RoleController::class, 'getPermissions']);

    Route::get('/drying-process/error-data', [DryingProcessController::class, 'getErrorData'])->name('drying-process.error-data');

});

    Route::get('/drying-process', [DryerProcessController::class, 'index']);
    Route::get('/drying-process/{process_id}', [DryerProcessController::class, 'show']);

Route::get('/jenis-gabah', [JenisGabahController::class, 'index']);

Route::get('/training-data', [TrainingDataController::class, 'index']);

Route::get('/get_sensor/realtime', [SensorController::class, 'getLatestSensorData']);

Route::get('/devices', [DeviceController::class, 'index']);
Route::post('/devices', [DeviceController::class, 'store']);
Route::put('/devices/{id}', [DeviceController::class, 'update']);
Route::delete('/devices/{id}', [DeviceController::class, 'destroy']);
Route::get('/devices/averages', [DeviceController::class, 'averages']);
Route::get('/devices/{id}', [DeviceController::class, 'show']);

Route::get('/get-sensor', [SensorController::class, 'getByDevice']);
Route::post('/sensor', [SensorController::class, 'store']);
Route::get('/sensor-data', [PrediksiController::class, 'sensorData']);
Route::get('/data-sensor', [SensorController::class, 'index']);

Route::post('/save_prediction', [PredictionController::class, 'savePrediction']);
Route::get('/predictions', [PredictionController::class, 'index']);

Route::post('/start_drying_process', [DryerProcessController::class, 'startDryingProcess']);
Route::post('/update_drying_process', [DryerProcessController::class, 'updateDryingProcess']);
// Route::post('/save_prediction', [PredictionEstimationController::class, 'store']);
// Route::get('/prediction_estimations/{process_id}', [PredictionEstimationController::class, 'index']);
Route::get('/prediction_estimations/{process_id}', [DryerProcessController::class, 'getPredictionEstimations']);

Route::post('/import-training-data', [TrainingDataImportController::class, 'import']);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::post('/predict', function (Request $request) {
    // Ambil data dari request
    $data = $request->only([
        'grain_temperature',
        'room_temperature',
        'grain_moisture',
        'weight',
        'combustion_temperature'
    ]);

    // Validasi data
    $validated = $request->validate([
        'grain_temperature' => 'required|numeric',
        'room_temperature' => 'required|numeric',
        'grain_moisture' => 'required|numeric|min:0',
        'weight' => 'required|numeric|min:0',
        'combustion_temperature' => 'nullable|numeric'
    ]);

    try {
        // Kirim data ke endpoint Flask yang benar
        $response = Http::timeout(300)->post('http://192.168.43.142:5000/prediksi', $data);
        
        // Periksa apakah respons dari Flask valid
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json([
                'error' => 'Failed to get prediction from ML service',
                'details' => $response->body()
            ], $response->status());
        }
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to connect to prediction service: ' . $e->getMessage()
        ], 500);
    }
});