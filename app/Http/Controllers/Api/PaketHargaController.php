<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaketHarga;
use Illuminate\Http\Request;

class PaketHargaController extends Controller
{
    public function index()
    {
        return response()->json(PaketHarga::all());
    }

    public function update(Request $request, $id)
    {
        $paket = PaketHarga::findOrFail($id);
        $paket->update($request->only(['nama_paket', 'harga']));
        return response()->json(['message' => 'Paket harga berhasil diperbarui', 'data' => $paket]);
    }
}
