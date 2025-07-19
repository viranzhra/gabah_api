<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryerProcess;
use App\Models\PredictionEstimation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class DryerProcessController extends Controller
{
    public function index(Request $request)
    {
        $query = DryerProcess::query()
            ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
            ->select(
                'drying_process.process_id',
                'drying_process.user_id',
                'drying_process.grain_type_id',
                'drying_process.kadar_air_target',
                'drying_process.lokasi',
                'drying_process.berat_gabah_awal',
                'drying_process.berat_gabah_akhir',
                'drying_process.kadar_air_awal',
                'drying_process.kadar_air_akhir',
                'drying_process.avg_estimasi_durasi',
                'drying_process.durasi_aktual',
                'drying_process.durasi_rekomendasi',
                'drying_process.durasi_terlaksana',
                'drying_process.status',
                'drying_process.timestamp_mulai',
                'drying_process.timestamp_selesai',
                'drying_process.catatan',
                'grain_types.nama_jenis'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('berat_gabah_awal', function ($row) {
                return $row->berat_gabah_awal ? number_format($row->berat_gabah_awal, 2) : '-';
            })
            ->editColumn('berat_gabah_akhir', function ($row) {
                return $row->berat_gabah_akhir ? number_format($row->berat_gabah_akhir, 2) : '-';
            })
            ->editColumn('kadar_air_awal', function ($row) {
                return $row->kadar_air_awal ? number_format($row->kadar_air_awal, 2) : '-';
            })
            ->editColumn('kadar_air_target', function ($row) {
                return $row->kadar_air_target ? number_format($row->kadar_air_target, 2) : '-';
            })
            ->editColumn('kadar_air_akhir', function ($row) {
                return $row->kadar_air_akhir ? number_format($row->kadar_air_akhir, 2) : '-';
            })
            ->editColumn('avg_estimasi_durasi', function ($row) {
                return $row->avg_estimasi_durasi ? round($row->avg_estimasi_durasi) . ' menit' : '-';
            })
            ->editColumn('durasi_aktual', function ($row) {
                if ($row->durasi_aktual) {
                    $totalMinutes = intval($row->durasi_aktual);
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    return sprintf('%d jam %d menit', $hours, $minutes);
                }
                return '-';
            })
            ->editColumn('durasi_rekomendasi', function ($row) {
                if ($row->durasi_rekomendasi) {
                    $totalMinutes = intval($row->durasi_rekomendasi);
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    return sprintf('%d jam %d menit', $hours, $minutes);
                }
                return '-';
            })
            ->editColumn('durasi_terlaksana', function ($row) {
                if ($row->status === 'ongoing' && $row->timestamp_mulai) {
                    $start = Carbon::parse($row->timestamp_mulai);
                    $now = Carbon::now('Asia/Jakarta');
                    $totalMinutes = $start->diffInMinutes($now);
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    return sprintf('%d jam %d menit', $hours, $minutes);
                }
                if ($row->durasi_terlaksana) {
                    $totalMinutes = intval($row->durasi_terlaksana);
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    return sprintf('%d jam %d menit', $hours, $minutes);
                }
                return '0 jam 0 menit';
            })
            ->editColumn('timestamp_mulai', function ($row) {
                return $row->timestamp_mulai ? Carbon::parse($row->timestamp_mulai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-';
            })
            ->editColumn('timestamp_selesai', function ($row) {
                return $row->timestamp_selesai ? Carbon::parse($row->timestamp_selesai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-';
            })
            ->editColumn('status', function ($row) {
                return match ($row->status) {
                    'pending' => '<span class="status-pending">Menunggu</span>',
                    'ongoing' => '<span class="status-proses">Berjalan</span>',
                    'completed' => '<span class="status-selesai">Selesai</span>',
                    default => $row->status
                };
            })
            ->addColumn('aksi', function ($row) {
                if ($row->status === 'completed') {
                    return '-';
                } elseif ($row->status === 'pending') {
                    return '<button class="btn btn-sm btn-success btn-mulai" onclick="startProcess(' . $row->process_id . ')">Mulai</button>';
                } elseif ($row->status === 'ongoing') {
                    return '<button class="btn btn-sm btn-danger btn-selesai" data-process-id="' . $row->process_id . '">Selesai</button>';
                }
                return '-';
            })
            ->rawColumns(['status', 'aksi'])
            ->make(true);
    }

    public function startDryingProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'grain_type_id' => 'required|exists:grain_types,grain_type_id',
            'kadar_air_target' => 'required|numeric',
            'lokasi' => 'nullable|string|max:100',
            'berat_gabah_awal' => 'nullable|numeric',
            'kadar_air_awal' => 'nullable|numeric',
            'status' => 'required|in:pending,ongoing,completed',
            'timestamp_mulai' => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for startDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $process = DryerProcess::create([
            'user_id' => $request->user_id,
            'grain_type_id' => $request->grain_type_id,
            'kadar_air_target' => $request->kadar_air_target,
            'lokasi' => $request->lokasi,
            'berat_gabah_awal' => $request->berat_gabah_awal,
            'kadar_air_awal' => $request->kadar_air_awal,
            'status' => $request->status,
            'timestamp_mulai' => $request->timestamp_mulai,
            'durasi_rekomendasi' => 0
        ]);

        Log::info("Drying process started: " . json_encode($process->toArray()));
        return response()->json(['message' => 'Drying process started successfully', 'process_id' => $process->process_id], 201);
    }

    public function updateDryingProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|exists:drying_process,process_id',
            'avg_estimasi_durasi' => 'nullable|numeric',
            'timestamp_selesai' => 'nullable|date_format:Y-m-d H:i:s',
            'status' => 'nullable|in:pending,ongoing,completed',
            'kadar_air_akhir' => 'nullable|numeric',
            'durasi_aktual' => 'nullable|numeric',
            'durasi_rekomendasi' => 'nullable|numeric',
            'durasi_terlaksana' => 'nullable|numeric',
            'berat_gabah_akhir' => 'nullable|numeric',
            'catatan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for updateDryingProcess: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $process = DryerProcess::findOrFail($request->process_id);

        $fields = array_filter([
            'avg_estimasi_durasi' => $request->has('avg_estimasi_durasi') ? floatval($request->avg_estimasi_durasi) : null,
            'timestamp_selesai' => $request->timestamp_selesai,
            'status' => $request->status,
            'kadar_air_akhir' => $request->kadar_air_akhir,
            'durasi_aktual' => $request->durasi_aktual,
            'durasi_rekomendasi' => $request->durasi_rekomendasi,
            'durasi_terlaksana' => $request->durasi_terlaksana,
            'berat_gabah_akhir' => $request->berat_gabah_akhir,
            'catatan' => $request->catatan
        ]);

        Log::info("Received fields for update: " . json_encode($request->all()));
        Log::info("Fields to update drying process: " . json_encode($fields));
        Log::info("Fillable fields in model: " . json_encode($process->getFillable()));

        $process->update($fields);
        $updatedProcess = $process->fresh();

        Log::info("Drying process updated: " . json_encode($updatedProcess->toArray()));

        return response()->json(['message' => 'Drying process updated successfully', 'data' => $updatedProcess], 200);
    }

    public function getPredictionEstimations(Request $request, $process_id)
    {
        $validator = Validator::make(['process_id' => $process_id], [
            'process_id' => 'required|exists:drying_process,process_id'
        ]);

        if ($validator->fails()) {
            Log::error("Validation failed for getPredictionEstimations: " . json_encode($validator->errors()));
            return response()->json(['error' => $validator->errors()], 422);
        }

        $estimations = PredictionEstimation::where('process_id', $process_id)->get();
        return response()->json([
            'estimations' => $estimations->map(function ($estimation) {
                return [
                    'id' => $estimation->id,
                    'process_id' => $estimation->process_id,
                    'estimasi_durasi' => floatval($estimation->estimasi_durasi),
                    'timestamp' => $estimation->timestamp
                ];
            })->toArray()
        ], 200);
    }

    public function show($process_id)
{
    $validator = Validator::make(['process_id' => $process_id], [
        'process_id' => 'required|exists:drying_process,process_id'
    ]);

    if ($validator->fails()) {
        Log::error("Validation failed for getDryingProcess: " . json_encode($validator->errors()));
        return response()->json(['error' => $validator->errors()], 422);
    }

    $process = DryerProcess::query()
        ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
        ->select(
            'drying_process.process_id',
            'drying_process.user_id',
            'drying_process.grain_type_id',
            'drying_process.kadar_air_target',
            'drying_process.lokasi',
            'drying_process.berat_gabah_awal',
            'drying_process.kadar_air_awal',
            'drying_process.status',
            'drying_process.timestamp_mulai',
            'grain_types.nama_jenis'
        )
        ->where('drying_process.process_id', $process_id)
        ->first();

    if (!$process) {
        Log::error("Drying process not found for process_id: {$process_id}");
        return response()->json(['error' => 'Drying process not found'], 404);
    }

    Log::info("Drying process retrieved: " . json_encode($process->toArray()));
    return response()->json(['data' => $process], 200);
}

}