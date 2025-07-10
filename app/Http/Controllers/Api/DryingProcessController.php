<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DryingProcess;
use App\Models\SensorData;
use Illuminate\Support\Facades\Log;

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
                'drying_process.suhu_pembakaran_awal',
                'drying_process.suhu_pembakaran_akhir',
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
            ->editColumn('suhu_pembakaran_awal', function ($row) {
                return $row->suhu_pembakaran_awal ? number_format($row->suhu_pembakaran_awal, 2) : '-';
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
            ->editColumn('suhu_pembakaran_akhir', function ($row) {
                return $row->suhu_pembakaran_akhir ? number_format($row->suhu_pembakaran_akhir, 2) : '-';
            })
            ->editColumn('durasi_rekomendasi', function ($row) {
                Log::debug('Formatting durasi_rekomendasi', ['process_id' => $row->process_id, 'durasi' => $row->durasi_rekomendasi]);
                if ($row->durasi_rekomendasi === null || $row->durasi_rekomendasi === '') {
                    return '-';
                }
                $totalMinutes = floatval($row->durasi_rekomendasi);
                $totalSeconds = $totalMinutes * 60;
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = floor($totalSeconds % 60);
                return sprintf('%d jam %d menit %d detik | %d menit', $hours, $minutes, $seconds, round($totalMinutes));
            })
            ->editColumn('durasi_aktual', function ($row) {
                Log::debug('Formatting durasi_aktual', ['process_id' => $row->process_id, 'durasi_aktual' => $row->durasi_aktual]);
                if ($row->durasi_aktual) {
                    $totalMinutes = intval($row->durasi_aktual);
                    $totalSeconds = $totalMinutes * 60;
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = floor($totalSeconds % 60);
                    return sprintf('%d jam %d menit %d detik | %d menit', $hours, $minutes, $seconds, $totalMinutes);
                }
                return '-';
            })
            ->editColumn('durasi_terlaksana', function ($row) {
                Log::debug('Calculating durasi_terlaksana', [
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
                    $totalMinutes = round($totalSeconds / 60);
                    return sprintf('%d jam %d menit %d detik | %d menit', $hours, $minutes, $seconds, $totalMinutes);
                }
                if ($row->durasi_terlaksana) {
                    $totalMinutes = intval($row->durasi_terlaksana);
                    $totalSeconds = $totalMinutes * 60;
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);
                    $seconds = floor($totalSeconds % 60);
                    return sprintf('%d jam %d menit %d detik | %d menit', $hours, $minutes, $seconds, $totalMinutes);
                }
                return '0 jam 0 menit 0 detik | 0 menit';
            })
            ->editColumn('timestamp_mulai', function ($row) {
                return $row->timestamp_mulai ? Carbon::parse($row->timestamp_mulai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : '-';
            })
            ->editColumn('timestamp_selesai', function ($row) {
                return $row->status === 'completed' && $row->timestamp_selesai
                    ? Carbon::parse($row->timestamp_selesai)->timezone('Asia/Jakarta')->format('d-m-Y H:i')
                    : '-';
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
            // Log data yang diterima
            Log::debug('Received data for drying process store', ['request' => $request->all()]);

            // Cek apakah ada proses ongoing untuk user yang sama
            $ongoingProcess = DryingProcess::where('user_id', auth()->id())
                ->where('status', 'ongoing')
                ->first();

            if ($ongoingProcess) {
                Log::warning('Attempt to create new drying process while ongoing process exists', [
                    'user_id' => auth()->id(),
                    'ongoing_process_id' => $ongoingProcess->process_id
                ]);
                return response()->json([
                    'error' => 'Tidak dapat memulai proses pengeringan baru karena ada proses pengeringan yang sedang berjalan.'
                ], 400);
            }

            // Validasi data
            $validated = $request->validate([
                'nama_jenis' => 'required|string|max:255',
                'suhu_gabah_awal' => 'required|numeric|min:0',
                'suhu_ruangan_awal' => 'required|numeric|min:0',
                'suhu_pembakaran_awal' => 'required|numeric|gt:0',
                'kadar_air_awal' => 'required|numeric|min:0|max:100',
                'kadar_air_target' => 'required|numeric|min:0|max:100',
                'berat_gabah' => 'required|numeric|min:0.1',
                'durasi_rekomendasi' => 'required|numeric|min:0',
            ], [
                'nama_jenis.required' => 'Nama jenis gabah wajib diisi.',
                'suhu_gabah_awal.required' => 'Suhu gabah awal wajib diisi.',
                'suhu_gabah_awal.numeric' => 'Suhu gabah awal harus berupa angka.',
                'suhu_gabah_awal.min' => 'Suhu gabah awal tidak boleh kurang dari 0.',
                'suhu_ruangan_awal.required' => 'Suhu ruangan awal wajib diisi.',
                'suhu_ruangan_awal.numeric' => 'Suhu ruangan awal harus berupa angka.',
                'suhu_ruangan_awal.min' => 'Suhu ruangan awal tidak boleh kurang dari 0.',
                'suhu_pembakaran_awal.required' => 'Suhu pembakaran awal wajib diisi.',
                'suhu_pembakaran_awal.numeric' => 'Suhu pembakaran awal harus berupa angka.',
                'suhu_pembakaran_awal.gt' => 'Suhu pembakaran awal harus lebih besar dari 0.',
                'kadar_air_awal.required' => 'Kadar air awal wajib diisi.',
                'kadar_air_awal.numeric' => 'Kadar air awal harus berupa angka.',
                'kadar_air_awal.min' => 'Kadar air awal tidak boleh kurang dari 0.',
                'kadar_air_awal.max' => 'Kadar air awal tidak boleh lebih dari 100.',
                'kadar_air_target.required' => 'Kadar air target wajib diisi.',
                'kadar_air_target.numeric' => 'Kadar air target harus berupa angka.',
                'kadar_air_target.min' => 'Kadar air target tidak boleh kurang dari 0.',
                'kadar_air_target.max' => 'Kadar air target tidak boleh lebih dari 100.',
                'berat_gabah.required' => 'Berat gabah wajib diisi.',
                'berat_gabah.numeric' => 'Berat gabah harus berupa angka.',
                'berat_gabah.min' => 'Berat gabah harus lebih besar dari 0.',
                'durasi_rekomendasi.required' => 'Durasi rekomendasi wajib diisi.',
                'durasi_rekomendasi.numeric' => 'Durasi rekomendasi harus berupa angka.',
                'durasi_rekomendasi.min' => 'Durasi rekomendasi tidak boleh kurang dari 0.'
            ]);

            // Log data yang telah divalidasi
            Log::debug('Validated data for drying process store', ['validated' => $validated]);

            if (!auth()->check()) {
                Log::warning('Unauthorized attempt to store drying process', ['request' => $request->all()]);
                return response()->json(['error' => 'Pengguna tidak terautentikasi. Silakan login.'], 401);
            }

            $grainType = \App\Models\GrainType::where('nama_jenis', $validated['nama_jenis'])->first();
            if (!$grainType) {
                Log::error('Grain type not found', ['nama_jenis' => $validated['nama_jenis']]);
                return response()->json(['error' => 'Jenis gabah tidak ditemukan'], 404);
            }

            // Buat proses pengeringan
            $process = DryingProcess::create([
                'user_id' => auth()->id(),
                'grain_type_id' => $grainType->grain_type_id,
                'timestamp_mulai' => Carbon::now('Asia/Jakarta'),
                'berat_gabah' => $validated['berat_gabah'],
                'kadar_air_target' => $validated['kadar_air_target'],
                'kadar_air_awal' => $validated['kadar_air_awal'],
                'suhu_gabah_awal' => $validated['suhu_gabah_awal'],
                'suhu_ruangan_awal' => $validated['suhu_ruangan_awal'],
                'suhu_pembakaran_awal' => $validated['suhu_pembakaran_awal'],
                'durasi_rekomendasi' => $validated['durasi_rekomendasi'],
                'status' => 'ongoing',
                'durasi_terlaksana' => 0,
                'durasi_aktual' => 0,
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_at' => Carbon::now('Asia/Jakarta')
            ]);

            Log::info('Drying process created successfully', [
                'process_id' => $process->process_id,
                'status' => $process->status,
                'suhu_pembakaran_awal' => $validated['suhu_pembakaran_awal']
            ]);

            return response()->json(['success' => true, 'data' => $process], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in store drying process', ['errors' => $e->errors(), 'request' => $request->all()]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))], 422);
        } catch (\Exception $e) {
            Log::error('Error storing drying process: ' . $e->getMessage(), ['request' => $request->all()]);
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

            // Cek apakah ada proses ongoing lain untuk user yang sama
            $ongoingProcess = DryingProcess::where('user_id', auth()->id())
                ->where('status', 'ongoing')
                ->where('process_id', '!=', $id)
                ->first();

            if ($ongoingProcess) {
                Log::warning('Attempt to start drying process while another ongoing process exists', [
                    'user_id' => auth()->id(),
                    'ongoing_process_id' => $ongoingProcess->process_id,
                    'attempted_process_id' => $id
                ]);
                return response()->json([
                    'error' => 'Tidak dapat memulai proses ini karena ada proses pengeringan lain yang sedang berjalan.'
                ], 400);
            }

            $process->update([
                'status' => 'ongoing',
                'timestamp_mulai' => Carbon::now('Asia/Jakarta'),
                'durasi_terlaksana' => 0,
                'durasi_aktual' => 0
            ]);

            Log::info('Drying process started', ['process_id' => $id]);

            return response()->json(['success' => true, 'message' => 'Proses dimulai']);
        } catch (\Exception $e) {
            Log::error('Error starting drying process: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memulai proses: ' . $e->getMessage()], 500);
        }
    }

    public function complete(Request $request, $processId)
    {
        try {
            $process = DryingProcess::where('process_id', $processId)->firstOrFail();
            if ($process->status !== 'ongoing') {
                return response()->json(['error' => 'Proses tidak dalam status berjalan'], 400);
            }

            // Validasi input
            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'suhu_gabah_akhir' => 'required|numeric|min:0',
                'suhu_ruangan_akhir' => 'required|numeric|min:0',
                'suhu_pembakaran_akhir' => 'required|numeric|min:0',
                'timestamp_selesai' => 'required|date'
            ]);

            // Hitung durasi aktual
            $startTime = Carbon::parse($process->timestamp_mulai);
            $endTime = Carbon::parse($validated['timestamp_selesai']);
            $durasiAktual = $startTime->diffInMinutes($endTime);

            // Update proses pengeringan
            $process->kadar_air_akhir = $validated['kadar_air_akhir'];
            $process->suhu_gabah_akhir = $validated['suhu_gabah_akhir'];
            $process->suhu_ruangan_akhir = $validated['suhu_ruangan_akhir'];
            $process->suhu_pembakaran_akhir = $validated['suhu_pembakaran_akhir'];
            $process->timestamp_selesai = $endTime;
            $process->durasi_aktual = $durasiAktual;
            $process->durasi_terlaksana = $durasiAktual;
            $process->status = 'completed';
            $process->save();

            // Update sensor data dengan timestamp_selesai
            SensorData::where('process_id', $processId)
                ->where('timestamp', '<=', $endTime)
                ->update(['timestamp_selesai' => $endTime]);

            return response()->json([
                'success' => true,
                'message' => 'Proses pengeringan selesai',
                'data' => [
                    'process_id' => $process->process_id,
                    'kadar_air_akhir' => $process->kadar_air_akhir,
                    'suhu_gabah_akhir' => $process->suhu_gabah_akhir,
                    'suhu_ruangan_akhir' => $process->suhu_ruangan_akhir,
                    'suhu_pembakaran_akhir' => $process->suhu_pembakaran_akhir,
                    'timestamp_selesai' => $process->timestamp_selesai,
                    'durasi_aktual' => $process->durasi_aktual,
                    'status' => $process->status
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Gagal menyelesaikan proses: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menyelesaikan proses'], 500);
        }
    }

    public function updateDuration(Request $request, $id)
    {
        try {
            $process = DryingProcess::findOrFail($id);
            if ($process->status !== 'ongoing') {
                Log::warning("Cannot update duration for non-ongoing process", ['process_id' => $id, 'status' => $process->status]);
                return response()->json(['error' => 'Proses tidak dalam status ongoing'], 400);
            }

            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'suhu_gabah_akhir' => 'required|numeric|min:0',
                'suhu_ruangan_akhir' => 'required|numeric|min:0',
                'suhu_pembakaran_akhir' => 'required|numeric|min:0',
                'durasi_terlaksana' => 'required|numeric|min:0'
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
                'durasi_terlaksana.required' => 'Durasi terlaksana wajib diisi.',
                'durasi_terlaksana.numeric' => 'Durasi terlaksana harus berupa angka.',
                'durasi_terlaksana.min' => 'Durasi terlaksana tidak boleh kurang dari 0.'
            ]);

            $start = Carbon::parse($process->timestamp_mulai);
            $now = Carbon::now('Asia/Jakarta');
            $durasi = $validated['durasi_terlaksana'];

            Log::info("Updating duration for process", [
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
                    'suhu_pembakaran_akhir' => $validated['suhu_pembakaran_akhir'],
                    'durasi_aktual' => $durasi,
                    'durasi_terlaksana' => $durasi
                ]);
                Log::info("Process completed automatically", ['process_id' => $id]);
                return response()->json(['success' => true, 'message' => 'Proses selesai secara otomatis']);
            }

            $process->update([
                'durasi_terlaksana' => $durasi,
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'suhu_gabah_akhir' => $validated['suhu_gabah_akhir'],
                'suhu_ruangan_akhir' => $validated['suhu_ruangan_akhir'],
                'suhu_pembakaran_akhir' => $validated['suhu_pembakaran_akhir']
            ]);
            Log::info("Duration updated", ['process_id' => $id, 'durasi' => $durasi]);
            return response()->json(['success' => true, 'message' => 'Durasi diperbarui, proses masih berjalan']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating duration', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Validasi gagal: ' . implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))], 422);
        } catch (\Exception $e) {
            Log::error('Error updating duration: ' . $e->getMessage(), ['process_id' => $id]);
            return response()->json(['error' => 'Gagal memproses: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $process = DB::table('drying_process')
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
                    'drying_process.suhu_pembakaran_awal',
                    'drying_process.suhu_pembakaran_akhir',
                    'drying_process.durasi_rekomendasi',
                    'drying_process.durasi_aktual',
                    'drying_process.durasi_terlaksana',
                    'drying_process.status',
                    'grain_types.nama_jenis'
                )
                ->where('drying_process.process_id', $id)
                ->first();

            if (!$process) {
                Log::warning('Drying process not found', ['process_id' => $id]);
                return response()->json(['error' => 'Proses tidak ditemukan'], 404);
            }

            $formattedProcess = [
                'process_id' => $process->process_id,
                'nama_jenis' => $process->nama_jenis,
                'berat_gabah' => $process->berat_gabah ? number_format($process->berat_gabah, 2) : null,
                'kadar_air_target' => $process->kadar_air_target ? number_format($process->kadar_air_target, 2) : null,
                'kadar_air_awal' => $process->kadar_air_awal ? number_format($process->kadar_air_awal, 2) : null,
                'kadar_air_akhir' => $process->kadar_air_akhir ? number_format($process->kadar_air_akhir, 2) : null,
                'suhu_gabah_awal' => $process->suhu_gabah_awal ? number_format($process->suhu_gabah_awal, 2) : null,
                'suhu_gabah_akhir' => $process->suhu_gabah_akhir ? number_format($process->suhu_gabah_akhir, 2) : null,
                'suhu_ruangan_awal' => $process->suhu_ruangan_awal ? number_format($process->suhu_ruangan_awal, 2) : null,
                'suhu_ruangan_akhir' => $process->suhu_ruangan_akhir ? number_format($process->suhu_ruangan_akhir, 2) : null,
                'suhu_pembakaran_awal' => $process->suhu_pembakaran_awal ? number_format($process->suhu_pembakaran_awal, 2) : null,
                'suhu_pembakaran_akhir' => $process->suhu_pembakaran_akhir ? number_format($process->suhu_pembakaran_akhir, 2) : null,
                'durasi_rekomendasi' => $this->formatDuration($process->durasi_rekomendasi),
                'durasi_aktual' => $this->formatDuration($process->durasi_aktual),
                'durasi_terlaksana' => $this->formatTerlaksana($process),
                'timestamp_mulai' => $process->timestamp_mulai ? Carbon::parse($process->timestamp_mulai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : null,
                'timestamp_selesai' => $process->timestamp_selesai ? Carbon::parse($process->timestamp_selesai)->timezone('Asia/Jakarta')->format('d-m-Y H:i') : null,
                'status' => $process->status,
            ];

            Log::info('Drying process detail retrieved', ['process_id' => $id]);

            return response()->json(['success' => true, 'data' => $formattedProcess], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving drying process detail: ' . $e->getMessage(), ['process_id' => $id]);
            return response()->json(['error' => 'Gagal mengambil detail proses: ' . $e->getMessage()], 500);
        }
    }

    public function getErrorData(Request $request)
{
    try {
        if (!auth()->check()) {
            return response()->json(['error' => 'Pengguna tidak terautentikasi'], 401);
        }

        $processes = DB::table('drying_process')
            ->where('user_id', auth()->id()) // Filter by authenticated user
            ->select(
                'process_id',
                'timestamp_mulai',
                'durasi_rekomendasi',
                'durasi_terlaksana'
            )
            ->where('status', 'completed')
            ->orderBy('timestamp_mulai', 'asc')
            ->get();

        $errorData = $processes->map(function ($process) {
            $error = floatval($process->durasi_terlaksana) - floatval($process->durasi_rekomendasi);
            return [
                'process_id' => $process->process_id,
                'timestamp_mulai' => Carbon::parse($process->timestamp_mulai)->timezone('Asia/Jakarta')->format('d-m-Y H:i'),
                'error' => round($error, 2)
            ];
        });

        Log::info('Error data retrieved for chart', ['count' => $errorData->count()]);

        return response()->json([
            'success' => true,
            'data' => $errorData
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error retrieving error data for chart: ' . $e->getMessage());
        return response()->json(['error' => 'Gagal mengambil data error: ' . $e->getMessage()], 500);
    }
}

    private function formatDuration($duration)
    {
        if ($duration === null || $duration === '') {
            return '-';
        }
        $totalSeconds = floatval($duration) * 60;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = floor($totalSeconds % 60);
        return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
    }

    private function formatTerlaksana($process)
    {
        if ($process->status === 'ongoing' && $process->timestamp_mulai) {
            $start = Carbon::parse($process->timestamp_mulai);
            $now = Carbon::now('Asia/Jakarta');
            $totalSeconds = $start->diffInSeconds($now);
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = floor($totalSeconds % 60);
            return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
        }
        if ($process->durasi_terlaksana) {
            $totalSeconds = intval($process->durasi_terlaksana) * 60;
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = floor($totalSeconds % 60);
            return sprintf('%d jam %d menit %d detik', $hours, $minutes, $seconds);
        }
        return '0 jam 0 menit 0 detik';
    }
}