<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
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

use App\Http\Controllers\Api\SSEController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\KontakController;

use App\Http\Controllers\Api\PaketHargaController;
use App\Http\Controllers\Api\PesananController;
use App\Http\Controllers\Api\BedDryerController;

use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\DashboardController;

use Illuminate\Support\Facades\Broadcast;


Route::get('/dashboard/admin-summary', [DashboardController::class, 'adminSummary']);

Route::post('/register', [AuthController::class, 'register']);
// Rute publik (tidak memerlukan autentikasi)
Route::post('login', [AuthController::class, 'login'])->name('login'); // Add name('login')

Route::get('/paket-harga', [PaketHargaController::class, 'index']);
Route::put('/paket-harga/{id}', [PaketHargaController::class, 'update']);

    // Route::get('/ongoing-process', [SSEController::class, 'getOngoingProcess']); // baru
    Route::get('/ongoing', [SSEController::class, 'ongoing']); 

    Route::get('/sse/sensor-data/{processId}', [SSEController::class, 'stream']);
    Route::get('/stream-interval/{processId}', [SSEController::class, 'streamInterval']);
    Route::get('/notifications/{processId}', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    // Route::get('/ongoing-process', [SSEController::class, 'getOngoingProcess']);
    Route::get('/historical-sensor-data/{processId}', [SSEController::class, 'getHistoricalSensorData']);

// Rute yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    Broadcast::routes();

    Route::get('/bed-dryers', [BedDryerController::class, 'index']);
    Route::post('/bed-dryers/store', [BedDryerController::class, 'store']);
    Route::get('/bed-dryers/{id}', [BedDryerController::class, 'show']);
    Route::put('/bed-dryers/{id}', [BedDryerController::class, 'update']);
    Route::delete('/bed-dryers/{id}', [BedDryerController::class, 'destroy']);

    // Route::get('/ongoing-process', [SSEController::class, 'getOngoingProcess']);

    Route::get('/warehouses', [WarehouseController::class, 'index'])
        ->name('warehouses.index');

    // GET /api/warehouses/{id} -> detail warehouse
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show'])
        ->whereNumber('id')
        ->name('warehouses.show');

    // PUT /api/warehouses/{id} -> update nama/deskripsi
    Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])
        ->whereNumber('id')
        ->name('warehouses.update');

    // DELETE /api/warehouses/{id} -> hapus warehouse
    Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])
        ->whereNumber('id')
        ->name('warehouses.destroy');

    Route::post('/warehouses/store', [WarehouseController::class, 'store'])
    ->name('warehouses.store');


    Route::get('/kontak', [KontakController::class, 'showContactInfo']);
    Route::put('/kontak/update', [KontakController::class, 'updateContactInfo']);
    Route::get('/pesan-user', [KontakController::class, 'listPesanUser']);
    Route::post('/pesan', [KontakController::class, 'storePesanUser']);

    Route::delete('/pesan-user/{id}', [KontakController::class, 'destroyPesanUser']);
    Route::put('/pesan-user/{id}/status', [KontakController::class, 'updateStatusPesanUser']);

    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/pesanan', [PesananController::class, 'index']);
    Route::post('/pesanan/store', [PesananController::class, 'store']);
    Route::put('/pesanan/{id}/status', [PesananController::class, 'updateStatus']);

    Route::get('/jenis-gabah', [JenisGabahController::class, 'index']);
    Route::get('/jenis-gabah/{id}', [JenisGabahController::class, 'show']);
    Route::post('/jenis-gabah', [JenisGabahController::class, 'store']);
    Route::put('/jenis-gabah/{id}', [JenisGabahController::class, 'update']);
    Route::delete('/jenis-gabah/{id}', [JenisGabahController::class, 'destroy']);

    // Route::post('/prediksi/store', [DryingProcessController::class, 'store']);
    // Route::get('/drying-process', [DryingProcessController::class, 'index']);
    // Route::get('/drying-process', [DryerProcessController::class, 'index']);
    // Route::post('/drying-process/{id}/start', [DryingProcessController::class, 'start']);
    Route::post('/drying-process/{id}/complete', [DryerProcessController::class, 'complete']);
    // Route::post('/drying-process/{id}/update-duration', [DryingProcessController::class, 'updateDuration']);
    // Route::get('/drying-process/{id}', [DryingProcessController::class, 'show']);

    // Route::get('/operator/prediksi', [OperatorDryingProcessController::class, 'index']);
    // Route::post('/operator/prediksi/store', [OperatorDryingProcessController::class, 'store']);
    // Route::post('/operator/drying-process/{id}/complete', [OperatorDryingProcessController::class, 'complete']);
    // Route::post('/operator/drying-process/{id}/update-duration', [OperatorDryingProcessController::class, 'updateDuration']);

    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'edit']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::get('/roles/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::get('/users', [RoleController::class, 'index']);
    Route::post('/users', [RoleController::class, 'storeUser']);
    Route::get('/users/{id}', [RoleController::class, 'showUser']);
    Route::put('/users/{id}', [RoleController::class, 'updateUser']);
    Route::delete('/users/{id}', [RoleController::class, 'deleteUser']);

    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::get('/riwayat-proses', [DryerProcessController::class, 'riwayat']);
    Route::get('/sensor-detail/{process_id}', [DryerProcessController::class, 'detail']);
});
    Route::get('/get_sensor/realtime', [SensorController::class, 'getLatestSensorData']);

    // Route::get('/drying-process/error-data', [DryingProcessController::class, 'getErrorData'])->name('drying-process.error-data');
    Route::post('/start_drying_process', [DryerProcessController::class, 'startDryingProcess']);
Route::post('/update_drying_process/{process_id}', [DryerProcessController::class, 'updateDryingProcess']);
Route::get('/drying-process', [DryerProcessController::class, 'index']);
Route::post('/validasi/{process_id}', [DryerProcessController::class, 'validateProcess']);
Route::get('/drying-process/{process_id}', [DryerProcessController::class, 'show']);


Route::post('/save_prediction', [DryerProcessController::class, 'savePrediction']);
Route::get('/roles', [RoleController::class, 'index']);

// Route::get('/jenis-gabah', [JenisGabahController::class, 'index']);

Route::get('/training-data', [TrainingDataController::class, 'index']);

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

// Route::post('/save_prediction', [PredictionEstimationController::class, 'store']);
// Route::get('/prediction_estimations/{process_id}', [PredictionEstimationController::class, 'index']);
Route::get('/prediction_estimations/{process_id}', [DryerProcessController::class, 'getPredictionEstimations']);

Route::post('/import-training-data', [TrainingDataImportController::class, 'import']);

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;

// Route::post('/predict', function (Request $request) {
//     // Ambil data dari request
//     $data = $request->only([
//         'grain_temperature',
//         'room_temperature',
//         'grain_moisture',
//         'weight',
//         'combustion_temperature'
//     ]);

//     // Validasi data
//     $validated = $request->validate([
//         'grain_temperature' => 'required|numeric',
//         'room_temperature' => 'required|numeric',
//         'grain_moisture' => 'required|numeric|min:0',
//         'weight' => 'required|numeric|min:0',
//         'combustion_temperature' => 'nullable|numeric'
//     ]);

//     try {
//         // Kirim data ke endpoint Flask yang benar
//         $response = Http::timeout(300)->post('http://192.168.43.142:5000/prediksi', $data);

//         // Periksa apakah respons dari Flask valid
//         if ($response->successful()) {
//             return response()->json($response->json());
//         } else {
//             return response()->json([
//                 'error' => 'Failed to get prediction from ML service',
//                 'details' => $response->body()
//             ], $response->status());
//         }
//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => 'Failed to connect to prediction service: ' . $e->getMessage()
//         ], 500);
//     }
// });



//////////////////////////////////////////////////////////////////////

use App\Http\Controllers\Api_mobile\MobileNotificationController;
use App\Http\Controllers\Api_mobile\MobilePredictionController;
use App\Http\Controllers\Api_mobile\GrainTypeController;
use App\Http\Controllers\Api_mobile\MobileTrainingDataController;
use App\Http\Controllers\Api_mobile\RealtimeDataController;
use App\Http\Controllers\Api_mobile\MobileDryingProcessController;
use App\Http\Controllers\Api_mobile\SensorDevicesController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Notifications
    Route::get('/notifications', [MobileNotificationController::class, 'index']);
    Route::delete('/notifications/{id}', [MobileNotificationController::class, 'destroy']);

    // Prediction APIs (wajib login)
    Route::post('/prediction/start', [MobilePredictionController::class, 'startPrediction']);
    Route::post('/prediction/stop', [MobilePredictionController::class, 'stopPrediction']);

    // Bed Dryers
    Route::get('/mybed-dryers', [AuthController::class, 'myBedDryers']);

    Route::get('/drying-history', [MobileDryingProcessController::class, 'getHistory']);
    Route::get('/drying-process/{processId}', [MobileDryingProcessController::class, 'getProcessDetails']);
    Route::post('/drying-process/validate', [MobileDryingProcessController::class, 'validateProcess']);

    Route::get('/sensor-devices', [SensorDevicesController::class, 'index']);
    Route::post('/sensor-devices/{device}/reset-delete', [SensorDevicesController::class, 'resetAndDelete']);
});

Route::post('/sensor-devices/new', [SensorDevicesController::class, 'newSensor']);

Route::get('/grain-types', [GrainTypeController::class, 'index']);

Route::get('/dataset', [MobileTrainingDataController::class, 'index']);

Route::get('/realtime-data', [RealtimeDataController::class, 'index']);
Route::get('/dashboard-data', [RealtimeDataController::class, 'dashboardData']);

Route::post('/prediction/receive', [MobilePredictionController::class, 'receivePrediction']);