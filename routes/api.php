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

// Rute publik (tidak memerlukan autentikasi)
Route::post('login', [AuthController::class, 'login'])->name('login'); // Add name('login')

// Rute yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/jenis-gabah', [JenisGabahController::class, 'index']);
    Route::get('/jenis-gabah/{id}', [JenisGabahController::class, 'show']);
    Route::post('/jenis-gabah', [JenisGabahController::class, 'store']);
    Route::put('/jenis-gabah/{id}', [JenisGabahController::class, 'update']);
    Route::delete('/jenis-gabah/{id}', [JenisGabahController::class, 'destroy']);

    Route::post('/prediksi/store', [DryingProcessController::class, 'store']);
    Route::get('/drying-process', [DryingProcessController::class, 'index']);
    Route::post('/drying-process/{id}/start', [DryingProcessController::class, 'start']);
    Route::post('/drying-process/{id}/complete', [DryingProcessController::class, 'complete']);
    Route::post('/drying-process/{id}/update-duration', [DryingProcessController::class, 'updateDuration']);
    Route::get('/drying-process/{id}', [DryingProcessController::class, 'show']);

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