<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use App\Models\GrainType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GrainTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grainTypes = GrainType::all();
        return response()->json([
            'message' => 'Daftar jenis gabah berhasil diambil',
            'data' => $grainTypes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama_jenis' => ['required', 'string', 'max:100', 'unique:grain_types,nama_jenis'],
                'deskripsi' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        $grainType = GrainType::create($request->all());

        return response()->json([
            'message' => 'Jenis gabah berhasil ditambahkan',
            'data' => $grainType
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $grainType = GrainType::find($id);

        if (!$grainType) {
            return response()->json(['message' => 'Jenis gabah tidak ditemukan'], 404);
        }

        return response()->json([
            'message' => 'Detail jenis gabah berhasil diambil',
            'data' => $grainType
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $grainType = GrainType::find($id);

        if (!$grainType) {
            return response()->json(['message' => 'Jenis gabah tidak ditemukan'], 404);
        }

        try {
            $request->validate([
                'nama_jenis' => ['required', 'string', 'max:100', 'unique:grain_types,nama_jenis,' . $id . ',grain_type_id'],
                'deskripsi' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        $grainType->update($request->all());

        return response()->json([
            'message' => 'Jenis gabah berhasil diperbarui',
            'data' => $grainType
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $grainType = GrainType::find($id);

        if (!$grainType) {
            return response()->json(['message' => 'Jenis gabah tidak ditemukan'], 404);
        }

        $grainType->delete();

        return response()->json(['message' => 'Jenis gabah berhasil dihapus'], 200);
    }
}