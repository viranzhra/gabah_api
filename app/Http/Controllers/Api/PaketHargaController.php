<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaketHarga;

class PaketHargaController extends Controller
{
    public function index()
    {
        return response()->json(PaketHarga::all());
    }
}
