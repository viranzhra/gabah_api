<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    /**
     * Tampilkan semua warehouse
     */
public function index(Request $request)
{
    $user = $request->user(); // Ambil user dari token

    $warehouses = Warehouse::where('user_id', $user->id) // hanya milik user login
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Data warehouse berhasil diambil.',
        'data' => $warehouses
    ]);
}


    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nama' => 'required|string|max:100',
        'deskripsi' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Ambil user dari token Sanctum
    $user = $request->user();

    $warehouse = Warehouse::create([
        'user_id'   => $user->id,
        'nama'      => $request->nama,
        'deskripsi' => $request->deskripsi,
    ]);

    // Kembalikan dengan relasi user
    $warehouse->load('user:id,name,email');

    return response()->json([
        'status'  => true,
        'message' => 'Warehouse berhasil dibuat.',
        'data'    => $warehouse,
    ], 201);
}

    /**
     * Ambil detail warehouse berdasarkan ID
     */
    public function show($id)
    {
        $warehouse = Warehouse::with('user:id,name,email')->find($id);

        if (!$warehouse) {
            return response()->json([
                'status' => false,
                'message' => 'Warehouse tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail warehouse berhasil diambil.',
            'data' => $warehouse
        ]);
    }

    /**
     * Update data warehouse
     */
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'status' => false,
                'message' => 'Warehouse tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $warehouse->update([
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Warehouse berhasil diperbarui.',
            'data' => $warehouse
        ]);
    }

    /**
     * Hapus data warehouse
     */
    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'status' => false,
                'message' => 'Warehouse tidak ditemukan.'
            ], 404);
        }

        $warehouse->delete();

        return response()->json([
            'status' => true,
            'message' => 'Warehouse berhasil dihapus.'
        ]);
    }
}
