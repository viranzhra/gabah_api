<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BedDryer;
use App\Models\SensorDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BedDryerController extends Controller
{
    /**
     * Ambil semua bed dryer milik user login
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $dryers = BedDryer::where('user_id', $user->id)
            ->get(['dryer_id', 'nama', 'deskripsi']);

        return response()->json([
            'status' => true,
            'message' => 'Data bed dryer berhasil diambil.',
            'data' => $dryers
        ]);
    }

    /**
     * Simpan bed dryer baru
     */
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
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        $dryer = BedDryer::create([
            'user_id' => $user->id,
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Bed dryer berhasil ditambahkan.',
            'data' => $dryer
        ], 201);
    }

    /**
     * Ambil detail bed dryer + device yang terhubung
     */
    public function show($id)
    {
        $dryer = BedDryer::find($id);

        if (!$dryer) {
            return response()->json([
                'status' => false,
                'message' => 'Bed dryer tidak ditemukan.'
            ], 404);
        }

        $devices = SensorDevice::where('dryer_id', $id)
            ->get(['device_id', 'device_name', 'address', 'status']);

        return response()->json([
            'status' => true,
            'message' => 'Detail bed dryer berhasil diambil.',
            'data' => [
                'dryer' => $dryer,
                'devices' => $devices
            ]
        ]);
    }

    /**
     * Update bed dryer
     */
    public function update(Request $request, $id)
    {
        $dryer = BedDryer::find($id);

        if (!$dryer) {
            return response()->json([
                'status' => false,
                'message' => 'Bed dryer tidak ditemukan.'
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

        $dryer->update([
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Bed dryer berhasil diperbarui.',
            'data' => $dryer
        ]);
    }

    /**
     * Hapus bed dryer
     */
    public function destroy($id)
    {
        $dryer = BedDryer::find($id);

        if (!$dryer) {
            return response()->json([
                'status' => false,
                'message' => 'Bed dryer tidak ditemukan.'
            ], 404);
        }

        $dryer->delete();

        return response()->json([
            'status' => true,
            'message' => 'Bed dryer berhasil dihapus.'
        ]);
    }
}
