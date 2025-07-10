<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\GrainType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OperatorDryingProcessController extends Controller
{
    /**
     * Display a listing of drying processes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            Log::warning('Unauthorized access attempt to drying process index', ['request' => $request->all()]);
            return response()->json(['error' => 'Pengguna tidak terautentikasi'], 401);
        }

        $query = DB::table('drying_processes')
            ->join('grain_types', 'drying_processes.grain_type_id', '=', 'grain_types.grain_type_id')
            ->where('drying_processes.user_id', $userId)
            ->select(
                'drying_processes.process_id',
                'drying_processes.timestamp_mulai',
                'drying_processes.timestamp_selesai',
                'drying_processes.berat_gabah',
                'drying_processes.kadar_air_target',
                'drying_processes.kadar_air_awal',
                'drying_processes.kadar_air_akhir',
                'drying_processes.suhu_gabah_awal',
                'drying_processes.suhu_gabah_akhir',
                'drying_processes.suhu_ruangan_awal',
                'drying_processes.suhu_ruangan_akhir',
                'drying_processes.suhu_pembakaran_awal',
                'drying_processes.suhu_pembakaran_akhir',
                DB::raw('CAST(drying_processes.durasi_rekomendasi AS DECIMAL(10,2)) AS durasi_rekomendasi'),
                DB::raw('CAST(drying_processes.durasi_aktual AS DECIMAL(10,2)) AS durasi_aktual'),
                DB::raw('CAST(drying_processes.durasi_terlaksana AS DECIMAL(10,2)) AS durasi_terlaksana'),
                'drying_processes.status',
                'grain_types.nama_jenis as grain_type_nama_jenis'
            );

        if ($request->has('latest') && $request->latest == 1) {
            $query->where('drying_processes.status', 'ongoing')
                ->orderBy('drying_processes.timestamp_mulai', 'desc')
                ->limit(1);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('suhu_gabah_awal', fn($row) => $row->suhu_gabah_awal ? number_format($row->suhu_gabah_awal, 2) : '-')
            ->editColumn('suhu_ruangan_awal', fn($row) => $row->suhu_ruangan_awal ? number_format($row->suhu_ruangan_awal, 2) : '-')
            ->editColumn('suhu_pembakaran_awal', fn($row) => $row->suhu_pembakaran_awal ? number_format($row->suhu_pembakaran_awal, 2) : '-')
            ->editColumn('kadar_air_awal', fn($row) => $row->kadar_air_awal ? number_format($row->kadar_air_awal, 2) : '-')
            ->editColumn('kadar_air_target', fn($row) => $row->kadar_air_target ? number_format($row->kadar_air_target, 2) : '-')
            ->editColumn('suhu_gabah_akhir', fn($row) => $row->suhu_gabah_akhir ? number_format($row->suhu_gabah_akhir, 2) : '-')
            ->editColumn('suhu_ruangan_akhir', fn($row) => $row->suhu_ruangan_akhir ? number_format($row->suhu_ruangan_akhir, 2) : '-')
            ->editColumn('suhu_pembakaran_akhir', fn($row) => $row->suhu_pembakaran_akhir ? number_format($row->suhu_pembakaran_akhir, 2) : '-')
            ->editColumn('durasi_rekomendasi', function ($row) {
                Log::debug('Formatting durasi_rekomendasi', ['process_id' => $row->process_id, 'durasi' => $row->durasi_rekomendasi]);
                if ($row->durasi_rekomendasi === null || $row->durasi_rekomendasi === '') return '-';
                return number_format($row->durasi_rekomendasi, 2);
            })
            ->editColumn('durasi_aktual', function ($row) {
                Log::debug('Formatting durasi_aktual', ['process_id' => $row->process_id, 'durasi_aktual' => $row->durasi_aktual]);
                return $row->durasi_aktual ? number_format($row->durasi_aktual, 2) : '-';
            })
            ->editColumn('durasi_terlaksana', function ($row) {
                Log::debug('Calculating durasi_terlaksana', [
                    'process_id' => $row->process_id,
                    'status' => $row->status,
                    'timestamp_mulai' => $row->timestamp_mulai,
                    'durasi_terlaksana' => $row->durasi_terlaksana
                ]);
                if ($row->status === 'ongoing' && $row->timestamp_mulai) {
                    $start = Carbon::parse($row->timestamp_mulai)->setTimezone('Asia/Jakarta');
                    $now = Carbon::now('Asia/Jakarta');
                    $minutes = $start->diffInMinutes($now);
                    return number_format($minutes, 2);
                }
                return $row->durasi_terlaksana ? number_format($row->durasi_terlaksana, 2) : '0.00';
            })
            ->editColumn('timestamp_mulai', fn($row) => $row->timestamp_mulai ? Carbon::parse($row->timestamp_mulai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-')
            ->editColumn('timestamp_selesai', fn($row) => $row->timestamp_selesai ? Carbon::parse($row->timestamp_selesai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-')
            ->editColumn('status', fn($row) => match ($row->status) {
                'pending' => '<span class="status-pending">Menunggu</span>',
                'ongoing' => '<span class="status-proses">Berjalan</span>',
                'completed' => '<span class="status-selesai">Selesai</span>',
                default => $row->status
            })
            ->addColumn('aksi', fn($row) => $row->status === 'completed' ? '-' : ($row->status === 'pending'
                ? '<button class="btn btn-sm btn-success btn-mulai" onclick="startProcess(' . $row->process_id . ')">Mulai</button>'
                : '<button class="btn btn-sm btn-danger btn-selesai" onclick="completeProcess(' . $row->process_id . ')">Selesai</button>'))
            ->addColumn('grain_type', fn($row) => ['nama_jenis' => $row->grain_type_nama_jenis])
            ->rawColumns(['status', 'aksi'])
            ->make(true);
    }

    /**
     * Store a new drying process.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_jenis' => 'required|string|max:255',
                'suhu_gabah_awal' => 'required|numeric|gte:0',
                'suhu_ruangan_awal' => 'required|numeric|gte:0',
                'suhu_pembakaran_awal' => 'required|numeric|gte:0',
                'kadar_air_awal' => 'required|numeric|between:0,100',
                'kadar_air_target' => 'required|numeric|between:0,100',
                'berat_gabah' => 'required|numeric|gt:0',
                'durasi_rekomendasi' => 'required|numeric|gte:0',
            ]);

            $grainType = GrainType::where('nama_jenis', $request->nama_jenis)->first();
            if (!$grainType) {
                return response()->json(['error' => 'Jenis gabah tidak ditemukan'], 404);
            }

            $process = DryingProcess::create([
                'user_id' => Auth::id(),
                'grain_type_id' => $grainType->grain_type_id,
                'status' => 'ongoing',
                'timestamp_mulai' => Carbon::now('Asia/Jakarta'),
                'berat_gabah' => $request->berat_gabah,
                'kadar_air_target' => $request->kadar_air_target,
                'kadar_air_awal' => $request->kadar_air_awal,
                'suhu_gabah_awal' => $request->suhu_gabah_awal,
                'suhu_ruangan_awal' => $request->suhu_ruangan_awal,
                'suhu_pembakaran_awal' => $request->suhu_pembakaran_awal,
                'durasi_rekomendasi' => $request->durasi_rekomendasi,
            ]);

            Log::info('Drying process created', ['process_id' => $process->process_id]);

            return response()->json([
                'success' => true,
                'message' => 'Proses pengeringan berhasil disimpan',
                'data' => ['process_id' => $process->process_id]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing drying process', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal menyimpan proses pengeringan'], 500);
        }
    }

    /**
     * Complete an ongoing drying process.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|between:0,100',
                'suhu_gabah_akhir' => 'required|numeric|gte:0',
                'suhu_ruangan_akhir' => 'required|numeric|gte:0',
                'suhu_pembakaran_akhir' => 'required|numeric|gte:0',
                'timestamp_selesai' => 'required|date_format:Y-m-d\TH:i:s.v\Z',
            ], [
                'timestamp_selesai.date_format' => 'Format timestamp_selesai harus ISO 8601 (contoh: 2025-06-17T15:34:00.000Z)',
            ]);

            Log::debug('Attempting to find drying process', [
                'process_id' => $id,
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            $process = DryingProcess::where('process_id', $id)->firstOrFail();

            if ($process->status !== 'ongoing') {
                Log::warning('Attempt to complete non-ongoing process', [
                    'process_id' => $id,
                    'status' => $process->status,
                    'user_id' => Auth::id(),
                ]);
                return response()->json(['error' => 'Proses tidak dalam status ongoing'], 400);
            }

            $startTime = Carbon::parse($process->timestamp_mulai)->setTimezone('Asia/Jakarta');
            $endTime = Carbon::parse($validated['timestamp_selesai'])->setTimezone('Asia/Jakarta');

            if ($endTime->lessThan($startTime)) {
                Log::error('Invalid timestamp_selesai: earlier than timestamp_mulai', [
                    'process_id' => $id,
                    'timestamp_mulai' => $process->timestamp_mulai,
                    'timestamp_selesai' => $validated['timestamp_selesai'],
                    'user_id' => Auth::id(),
                ]);
                return response()->json(['error' => 'Timestamp selesai tidak boleh lebih awal dari timestamp mulai'], 400);
            }

            $durasi_aktual = $startTime->diffInMinutes($endTime);

            DB::transaction(function () use ($process, $validated, $endTime, $durasi_aktual, $id) {
                $process->update([
                    'status' => 'completed',
                    'timestamp_selesai' => $endTime,
                    'kadar_air_akhir' => $validated['kadar_air_akhir'],
                    'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                    'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                    'suhu_pembakaran_akhir' => $validated['suhu_pembakaran_akhir'],
                    'durasi_aktual' => $durasi_aktual,
                    'durasi_terlaksana' => $durasi_aktual,
                ]);

                SensorData::where('process_id', $id)->update(['process_id' => null]);
            });

            Log::info('Drying process completed', [
                'process_id' => $id,
                'durasi_aktual' => $durasi_aktual,
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                'suhu_pembakaran_akhir' => $validated['suhu_pembakaran_akhir'],
                'user_id' => Auth::id(),
                'process_owner_user_id' => $process->user_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proses selesai'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error completing drying process', [
                'process_id' => $id,
                'errors' => $e->errors(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_merge(...array_values($e->errors())))], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Process not found', [
                'process_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Proses tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Error completing drying process', [
                'process_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Gagal menyelesaikan proses: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Start a pending drying process.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request, $id)
    {
        try {
            $process = DryingProcess::where('process_id', $id)->where('user_id', Auth::id())->firstOrFail();
            if ($process->status !== 'pending') {
                return response()->json(['error' => 'Proses tidak dalam status pending'], 400);
            }

            $process->update([
                'status' => 'ongoing',
                'timestamp_mulai' => Carbon::now('Asia/Jakarta'),
                'durasi_terlaksana' => 0,
                'durasi_aktual' => 0
            ]);

            Log::info('Drying process started', ['process_id' => $id]);

            return response()->json(['success' => true, 'message' => 'Proses dimulai']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Process not found or unauthorized', ['process_id' => $id, 'user_id' => Auth::id()]);
            return response()->json(['error' => 'Proses tidak ditemukan atau Anda tidak memiliki akses'], 404);
        } catch (\Exception $e) {
            Log::error('Error starting drying process: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memulai proses: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the duration of an ongoing drying process.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDuration(Request $request, $id)
    {
        try {
            $process = DryingProcess::where('process_id', $id)->where('user_id', Auth::id())->firstOrFail();
            if ($process->status !== 'ongoing') {
                Log::warning("Cannot update duration for non-ongoing process", ['process_id' => $id, 'status' => $process->status]);
                return response()->json(['error' => 'Proses tidak dalam status ongoing'], 400);
            }

            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'suhu_gabah_akhir' => 'required|numeric|min:0',
                'suhu_ruangan_akhir' => 'required|numeric|min:0',
                'suhu_pembakaran_akhir' => 'required|numeric|min:0'
            ], [
                'kadar_air_akhir.required' => 'Kadar air akhir wajib diisi.',
                'kadar_air_akhir.numeric' => 'Kadar air akhir harus berupa angka.',
                'kadar_air_akhir.min' => 'Kadar air akhir tidak boleh kurang dari 0.',
                'kadar_air_akhir.max' => 'Kadar air akhir tidak boleh lebih dari 100.',
                'suhu_gabah_akhir.required' => 'Suhu gabah akhir wajib diisi.',
                'suhu_gabah_akhir.numeric' => 'Suhu gabah akhir harus berupa angka.',
                'suhu_gabah_akhir.min' => 'Suhu gabah akhir tidak boleh kurang dari 0.',
                'suhu_ruangan_akhir.required' => 'Suhu ruangan akhir wajib diisi.',
                'suhu_ruangan_akhir.numeric' => 'Suhu ruangan akhir harus berupa angka.',
                'suhu_ruangan_akhir.min' => 'Suhu ruangan akhir tidak boleh kurang dari 0.',
                'suhu_pembakaran_akhir.required' => 'Suhu pembakaran akhir wajib diisi.',
                'suhu_pembakaran_akhir.numeric' => 'Suhu pembakaran akhir harus berupa angka.',
                'suhu_pembakaran_akhir.min' => 'Suhu pembakaran akhir tidak boleh kurang dari 0.',
            ]);

            $startTime = Carbon::parse($process->timestamp_mulai)->setTimezone('Asia/Jakarta');
            $now = Carbon::now('Asia/Jakarta');
            $durasi_terlaksana = $startTime->diffInMinutes($now);

            $process->update([
                'durasi_terlaksana' => $durasi_terlaksana,
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                'suhu_pembakaran_akhir' => $validated['suhu_pembakaran_akhir'],
            ]);

            Log::info('Drying process duration updated', [
                'process_id' => $id,
                'durasi_terlaksana' => $durasi_terlaksana,
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                'suhu_pembakaran_akhir' => $validated['suhu_pembakaran_akhir'],
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Durasi proses berhasil diperbarui',
                'data' => [
                    'durasi_terlaksana' => number_format($durasi_terlaksana, 2),
                    'kadar_air_akhir' => number_format($validated['kadar_air_akhir'], 2),
                    'suhu_gabah_akhir' => number_format($validated['suhu_gabah_akhir'], 2),
                    'suhu_ruangan_akhir' => number_format($validated['suhu_ruangan_akhir'], 2),
                    'suhu_pembakaran_akhir' => number_format($validated['suhu_pembakaran_akhir'], 2),
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating drying process duration', [
                'process_id' => $id,
                'errors' => $e->errors(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_merge(...array_values($e->errors())))], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Process not found or unauthorized', ['process_id' => $id, 'user_id' => Auth::id()]);
            return response()->json(['error' => 'Proses tidak ditemukan atau Anda tidak memiliki akses'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating drying process duration', [
                'process_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['error' => 'Gagal memperbarui durasi proses: ' . $e->getMessage()], 500);
        }
    }
}