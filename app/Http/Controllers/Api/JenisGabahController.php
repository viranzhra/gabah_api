<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GrainType;

class JenisGabahController extends Controller
{
    public function index()
    {
        $data = GrainType::select('grain_type_id', 'nama_jenis', 'deskripsi')->get();

        $data = $data->map(function ($item, $key) {
            $item->index = $key + 1;
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $item = GrainType::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $item
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama_jenis' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $item = GrainType::create([
            'nama_jenis' => $request->nama_jenis,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil disimpan',
            'data' => $item
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = GrainType::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        // Validasi input
        $request->validate([
            'nama_jenis' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $item->update([
            'nama_jenis' => $request->nama_jenis,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        $item = GrainType::find($id);

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
        ]);
    }
}
