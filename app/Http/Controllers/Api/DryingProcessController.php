<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DryingProcess;

class DryingProcessController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('drying_process')
            ->join('grain_types', 'drying_process.grain_type_id', '=', 'grain_types.grain_type_id')
            ->select(
                'drying_process.process_id',
                'drying_process.timestamp_mulai',
                'drying_process.timestamp_selesai',
                'drying_process.berat_gabah',
                'drying_process.kadar_air_target',
                'drying_process.kadar_air_awal',
                'drying_process.kadar_air_akhir',
                'drying_process.suhu_gabah_awal',
                'drying_process.suhu_gabah_akhir',
                'drying_process.suhu_ruangan_awal',
                'drying_process.suhu_ruangan_akhir',
                'drying_process.durasi_rekomendasi',
                'drying_process.durasi_aktual',
                'drying_process.durasi_terlaksana',
                'drying_process.status',
                'grain_types.nama_jenis'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('suhu_gabah_awal', function ($row) {
                return $row->suhu_gabah_awal ? number_format($row->suhu_gabah_awal, 2) : '-';
            })
            ->editColumn('suhu_ruangan_awal', function ($row) {
                return $row->suhu_ruangan_awal ? number_format($row->suhu_ruangan_awal, 2) : '-';
            })
            ->editColumn('kadar_air_awal', function ($row) {
                return $row->kadar_air_awal ? number_format($row->kadar_air_awal, 2) : '-';
            })
            ->editColumn('kadar_air_target', function ($row) {
                return $row->kadar_air_target ? number_format($row->kadar_air_target, 2) : '-';
            })
            ->editColumn('suhu_gabah_akhir', function ($row) {
                return $row->suhu_gabah_akhir ? number_format($row->suhu_gabah_akhir, 2) : '-';
            })
            ->editColumn('suhu_ruangan_akhir', function ($row) {
                return $row->suhu_ruangan_akhir ? number_format($row->suhu_ruangan_akhir, 2) : '-';
            })
            ->editColumn('durasi_rekomendasi', function ($row) {
                \Log::debug('Formatting durasi_rekomendasi', ['process_id' => $row->process_id, 'durasi' => $row->durasi_rekomendasi]);
                if ($row->durasi_rekomendasi === null || $row->durasi_rekomendasi === '') return '-';
                $totalSeconds = floatval($row->durasi_rekomendasi) * 60;
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = floor($totalSeconds % 60);
                return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
            })
            ->editColumn('durasi_aktual', function ($row) {
                \Log::debug('Formatting durasi_aktual', ['process_id' => $row->process_id, 'durasi_aktual' => $row->durasi_aktual]);
                if ($row->durasi_aktual) {
                    $totalSeconds = intval($row->durasi_aktual) * 60;
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = floor($totalSeconds % 60);
                    return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
                }
                return '-';
            })
            ->editColumn('durasi_terlaksana', function ($row) {
                \Log::debug('Calculating durasi_terlaksana', [
                    'process_id' => $row->process_id,
                    'status' => $row->status,
                    'timestamp_mulai' => $row->timestamp_mulai,
                    'durasi_terlaksana' => $row->durasi_terlaksana
                ]);
                if ($row->status === 'ongoing' && $row->timestamp_mulai) {
                    $start = Carbon::parse($row->timestamp_mulai);
                    $now = Carbon::now('Asia/Jakarta');
                    $totalSeconds = $start->diffInSeconds($now);
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = floor($totalSeconds % 60);
                    return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
                }
                if ($row->durasi_terlaksana) {
                    $totalSeconds = intval($row->durasi_terlaksana) * 60;
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = floor($totalSeconds % 60);
                    return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
                }
                return '0 jam 0 menit 0 detik';
            })
            ->editColumn('timestamp_mulai', function ($row) {
                return Carbon::parse($row->timestamp_mulai)->timezone('Asia/Jakarta')->format('d-m-Y H:i');
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
                    return '<button class="btn btn-sm btn-danger btn-selesai" onclick="completeProcess(' . $row->process_id . ')">Selesai</button>';
                }
            })
            ->rawColumns(['status', 'aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_jenis' => 'required|string',
                'suhu_gabah_awal' => 'required|numeric|min:0',
                'suhu_ruangan_awal' => 'required|numeric|min:0',
                'kadar_air_awal' => 'required|numeric|min:0|max:100',
                'kadar_air_target' => 'required|numeric|min:0|max:100',
                'berat_gabah' => 'required|numeric|min:0.1',
                'durasi_rekomendasi' => 'required|numeric|min:0',
                'timestamp_selesai' => 'required|date'
            ]);

            if (!auth()->check()) {
                \Log::warning('Unauthorized attempt to store drying process', ['request' => $request->all()]);
                return response()->json(['error' => 'Pengguna tidak terautentikasi. Silakan login.'], 401);
            }

            $grainType = \App\Models\GrainType::where('nama_jenis', $request->nama_jenis)->first();
            if (!$grainType) {
                \Log::error('Grain type not found', ['nama_jenis' => $request->nama_jenis]);
                return response()->json(['error' => 'Jenis gabah tidak ditemukan'], 404);
            }

            $process = DryingProcess::create([
                'user_id' => auth()->id(),
                'grain_type_id' => $grainType->grain_type_id,
                'timestamp_mulai' => Carbon::now('Asia/Jakarta'),
                'timestamp_selesai' => Carbon::parse($request->timestamp_selesai)->timezone('Asia/Jakarta'),
                'berat_gabah' => $request->berat_gabah,
                'kadar_air_target' => $request->kadar_air_target,
                'kadar_air_awal' => $request->kadar_air_awal,
                'suhu_gabah_awal' => $request->suhu_gabah_awal,
                'suhu_ruangan_awal' => $request->suhu_ruangan_awal,
                'durasi_rekomendasi' => $request->durasi_rekomendasi,
                'status' => 'ongoing',
                'durasi_terlaksana' => 0,
                'durasi_aktual' => 0,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta')
            ]);

            \Log::info('Drying process created successfully', ['process_id' => $process->process_id, 'status' => $process->status]);

            return response()->json(['success' => true, 'data' => $process], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in store drying process', ['errors' => $e->errors(), 'request' => $request->all()]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))], 422);
        } catch (\Exception $e) {
            \Log::error('Error storing drying process: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['error' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    public function start(Request $request, $id)
    {
        try {
            $process = DryingProcess::findOrFail($id);
            if ($process->status !== 'pending') {
                return response()->json(['error' => 'Proses tidak dalam status pending'], 400);
            }

            $process->update([
                'status' => 'ongoing',
                'timestamp_mulai' => Carbon::now('Asia/Jakarta'),
                'durasi_terlaksana' => 0,
                'durasi_aktual' => 0
            ]);

            \Log::info('Drying process started', ['process_id' => $id]);

            return response()->json(['success' => true, 'message' => 'Proses dimulai']);
        } catch (\Exception $e) {
            \Log::error('Error starting drying process: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memulai proses: ' . $e->getMessage()], 500);
        }
    }

    public function complete(Request $request, $id)
    {
        try {
            $process = DryingProcess::findOrFail($id);
            if ($process->status !== 'ongoing') {
                return response()->json(['error' => 'Proses tidak dalam status ongoing'], 400);
            }

            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'suhu_gabah_akhir' => 'required|numeric|min:0',
                'suhu_ruangan_akhir' => 'required|numeric|min:0',
                'timestamp_selesai' => 'required|date',
                'durasi_aktual' => 'nullable|integer|min:0'
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
                'timestamp_selesai.required' => 'Timestamp selesai wajib diisi.',
                'timestamp_selesai.date' => 'Timestamp selesai harus berupa tanggal yang valid.',
                'durasi_aktual.integer' => 'Durasi aktual harus berupa bilangan bulat.',
                'durasi_aktual.min' => 'Durasi aktual tidak boleh kurang dari 0.'
            ]);

            \Log::info('Validated complete data', ['process_id' => $id, 'data' => $validated]);

            $durasi_aktual = $validated['durasi_aktual'] ?? Carbon::parse($process->timestamp_mulai)->diffInMinutes(Carbon::parse($validated['timestamp_selesai']));

            $process->update([
                'status' => 'completed',
                'timestamp_selesai' => Carbon::parse($validated['timestamp_selesai'])->timezone('Asia/Jakarta'),
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                'durasi_aktual' => $durasi_aktual,
                'durasi_terlaksana' => $durasi_aktual
            ]);

            \Log::info('Drying process completed', [
                'process_id' => $id,
                'durasi_aktual' => $durasi_aktual,
                'kadar_air_akhir' => $validated['kadar_air_akhir']
            ]);

            return response()->json(['success' => true, 'message' => 'Proses selesai']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error completing drying process', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))], 422);
        } catch (\Exception $e) {
            \Log::error('Error completing drying process: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['error' => 'Gagal menyelesaikan proses: ' . $e->getMessage()], 500);
        }
    }

    public function updateDuration(Request $request, $id)
    {
        try {
            $process = DryingProcess::findOrFail($id);
            if ($process->status !== 'ongoing') {
                \Log::warning("Cannot update duration for non-ongoing process", ['process_id' => $id, 'status' => $process->status]);
                return response()->json(['error' => 'Proses tidak dalam status ongoing'], 400);
            }

            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'suhu_gabah_akhir' => 'required|numeric|min:0',
                'suhu_ruangan_akhir' => 'required|numeric|min:0'
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
                'suhu_ruangan_akhir.min' => 'Suhu ruangan akhir tidak boleh kurang dari 0.'
            ]);

            $start = Carbon::parse($process->timestamp_mulai);
            $now = Carbon::now('Asia/Jakarta');
            $durasi = $start->diffInMinutes($now);

            \Log::info("Updating duration for process", [
                'process_id' => $id,
                'durasi' => $durasi,
                'timestamp_mulai' => $process->timestamp_mulai,
                'now' => $now
            ]);

            if ($validated['kadar_air_akhir'] <= $process->kadar_air_target) {
                $process->update([
                    'status' => 'completed',
                    'timestamp_selesai' => $now,
                    'kadar_air_akhir' => $validated['kadar_air_akhir'],
                    'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                    'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                    'durasi_aktual' => $durasi,
                    'durasi_terlaksana' => $durasi
                ]);
                \Log::info("Process completed automatically", ['process_id' => $id]);
                return response()->json(['success' => true, 'message' => 'Proses selesai secara otomatis']);
            }

            $process->update([
                'durasi_terlaksana' => $durasi,
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir']
            ]);
            \Log::info("Duration updated", ['process_id' => $id, 'durasi' => $durasi]);
            return response()->json(['success' => true, 'message' => 'Durasi diperbarui, proses masih berjalan']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error updating duration', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating duration: ' . $e->getMessage(), ['process_id' => $id]);
            return response()->json(['error' => 'Gagal memproses: ' . $e->getMessage()], 500);
        }
    }
}