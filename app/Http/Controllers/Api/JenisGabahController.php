<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GrainType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class JenisGabahController extends Controller
{
    public function index()
    {
        try {
            $data = GrainType::where('user_id', Auth::id())
                             ->select('grain_type_id', 'nama_jenis', 'deskripsi')
                             ->get();
            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $item = GrainType::where('user_id', Auth::id())
                             ->find($id);
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }
            return response()->json([
                'status' => true,
                'data' => $item
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama_jenis' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item = GrainType::create([
                'user_id' => Auth::id(),
                'nama_jenis' => $request->nama_jenis,
                'deskripsi' => $request->deskripsi,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = GrainType::where('user_id', Auth::id())
                             ->find($id);
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_jenis' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item->update([
                'nama_jenis' => $request->nama_jenis,
                'deskripsi' => $request->deskripsi,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $item
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $item = GrainType::where('user_id', Auth::id())
                             ->find($id);
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $item->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}