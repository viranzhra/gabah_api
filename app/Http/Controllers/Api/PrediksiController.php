<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryingProcess;
use App\Models\GrainType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrediksiController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Ambil data untuk DataTable
            $query = DryingProcess::with('grainType')
                ->orderByDesc('created_at');

            // Jika serverSide: true, gunakan paginasi untuk DataTable
            $data = $query->get()->map(function ($process) {
                return [
                    'DT_RowIndex' => null, // Akan diisi oleh DataTable
                    'process_id' => $process->process_id,
                    'nama_jenis' => $process->grainType ? $process->grainType->nama_jenis : 'Tidak Diketahui',
                    'berat_gabah' => $process->berat_gabah,
                    'kadar_air_target' => $process->kadar_air_target,
                    'kadar_air_akhir' => $process->kadar_air_akhir,
                    'durasi_rekomendasi' => $process->durasi_rekomendasi,
                    'durasi_aktual' => $process->durasi_aktual,
                    'durasi_terlaksana' => $process->durasi_terlaksana,
                    'status' => $process->status,
                    'waktu_mulai' => $process->timestamp_mulai->format('Y-m-d H:i:s'),
                    'waktu_selesai' => $process->timestamp_selesai ? $process->timestamp_selesai->format('Y-m-d H:i:s') : null,
                    'aksi' => '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $process->process_id . '">Edit</button> ' .
                             '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $process->process_id . '">Hapus</button>',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching drying process data: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Log data yang diterima
            Log::info("Data diterima di /prediksi/store: " . json_encode($request->all()));

            // Validasi input
            $validated = $request->validate([
                'nama_jenis' => 'required|string|exists:grain_types,nama_jenis',
                'suhu_gabah' => 'required|numeric|min:0',
                'suhu_ruangan' => 'required|numeric|min:0',
                'suhu_pembakaran' => 'required|numeric|min:0',
                'kadar_air_awal' => 'required|numeric|min:0|max:100',
                'kadar_air_target' => 'required|numeric|min:0|max:100',
                'berat_gabah' => 'required|numeric|min:0.1',
                'durasi_rekomendasi' => 'required|numeric|min:0',
                'timestamp_mulai' => 'required|date',
                'timestamp_selesai' => 'required|date|after:timestamp_mulai',
            ]);

            // Cari grain_type_id berdasarkan nama_jenis
            $grainType = GrainType::where('nama_jenis', $validated['nama_jenis'])->first();

            if (!$grainType) {
                Log::error("Jenis gabah tidak ditemukan: {$validated['nama_jenis']}");
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis gabah tidak ditemukan.',
                ], 404);
            }

            // Simpan data ke tabel drying_process dengan status ongoing
            $data = DryingProcess::create([
                'user_id' => Auth::id() ?? 1,
                'grain_type_id' => $grainType->grain_type_id,
                'timestamp_mulai' => $validated['timestamp_mulai'],
                'timestamp_selesai' => $validated['timestamp_selesai'],
                'berat_gabah' => $validated['berat_gabah'],
                'kadar_air_target' => $validated['kadar_air_target'],
                'kadar_air_akhir' => null,
                'durasi_rekomendasi' => round($validated['durasi_rekomendasi']),
                'durasi_aktual' => null,
                'durasi_terlaksana' => 0,
                'status' => 'ongoing', // Langsung ongoing
            ]);

            Log::info("Data berhasil disimpan dengan process_id: {$data->process_id}");

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan dan proses pengeringan dimulai',
                'data' => $data,
                'process_id' => $data->process_id // Kembalikan process_id untuk digunakan di frontend
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation error: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error saving to drying_process: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Endpoint baru untuk memperbarui status proses pengeringan
    public function updateStatus(Request $request, $process_id)
    {
        try {
            $validated = $request->validate([
                'kadar_air_akhir' => 'required|numeric|min:0|max:100',
                'durasi_aktual' => 'required|numeric|min:0',
                'status' => 'required|in:ongoing,completed'
            ]);

            $process = DryingProcess::findOrFail($process_id);

            $process->update([
                'kadar_air_akhir' => $validated['kadar_air_akhir'],
                'durasi_aktual' => round($validated['durasi_aktual']),
                'durasi_terlaksana' => round($validated['durasi_aktual']),
                'status' => $validated['status'],
                'timestamp_selesai' => now() // Perbarui timestamp_selesai saat proses selesai
            ]);

            Log::info("Status proses pengeringan diperbarui untuk process_id: {$process_id}");

            return response()->json([
                'success' => true,
                'message' => 'Status proses berhasil diperbarui',
                'data' => $process
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation error updating process: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error updating process: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sensorData()
    {
        $sensorData = \App\Models\SensorData::whereNull('process_id')
            ->orderByDesc('timestamp')
            ->limit(4)
            ->get(['sensor_id', 'suhu_gabah', 'suhu_ruangan', 'suhu_pembakaran', 'kadar_air_gabah', 'timestamp']);

        if ($sensorData->count() < 4) {
            return response()->json([
                'success' => false,
                'message' => 'Data sensor belum lengkap (butuh 4 titik)',
                'data' => [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $sensorData,
        ]);
    }
}