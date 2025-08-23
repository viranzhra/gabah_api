<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        try {
            $pesanan = Pesanan::with(['user', 'paketHarga'])
                ->select('id', 'user_id', 'paket_id', 'alamat', 'catatan', 'bukti_pembayaran', 'nomor_struk', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $pesanan->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'user_name' => $item->user->name ?? 'N/A',
                        'paket_name' => $item->paketHarga->nama_paket ?? 'N/A',
                        'alamat' => $item->alamat,
                        'catatan' => $item->catatan ?? '-',
                        'bukti_pembayaran' => asset('storage/' . $item->bukti_pembayaran),
                        'nomor_struk' => $item->nomor_struk,
                        'status' => $item->status,
                        'created_at' => $item->created_at->format('d-m-Y H:i:s'),
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data pesanan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data pesanan: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,confirmed,cancelled',
            ], [
                'status.required' => 'Status harus diisi.',
                'status.in' => 'Status tidak valid. Pilih antara pending, confirmed, atau cancelled.',
            ]);

            $pesanan = Pesanan::findOrFail($id);

            $pesanan->update(['status' => $request->status]);

            return response()->json(['message' => 'Status pesanan berhasil diperbarui.']);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui status pesanan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui status pesanan: ' . $e->getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'paket_id'          => 'required|exists:paket_harga,id',
                'alamat'            => 'required|string|max:255',
                'catatan'           => 'nullable|string|max:1000',
                'bukti_pembayaran'  => 'required|file|mimes:jpg,jpeg,png|max:20480',
                'nomor_struk'       => 'required|string|max:20|unique:pesanan,nomor_struk', // validasi nomor_struk
            ], [
                'paket_id.required'         => 'Paket harus dipilih.',
                'paket_id.exists'           => 'Paket tidak valid.',
                'alamat.required'           => 'Alamat pengiriman diperlukan.',
                'alamat.max'                => 'Alamat maksimal 255 karakter.',
                'catatan.max'               => 'Catatan maksimal 1000 karakter.',
                'bukti_pembayaran.required' => 'Bukti pembayaran diperlukan.',
                'bukti_pembayaran.mimes'    => 'File harus berupa JPG atau PNG.',
                'bukti_pembayaran.max'      => 'Ukuran file maksimal 20MB.',
                'nomor_struk.required'      => 'Nomor struk harus diisi.',
                'nomor_struk.unique'        => 'Nomor struk sudah digunakan.',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
            }

            try {
                $path = $request->file('bukti_pembayaran')->store('bukti_pembayaran', 'public');
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan file: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal menyimpan bukti pembayaran: ' . $e->getMessage()], 500);
            }

            $nomorStruk = $request->nomor_struk; // ambil dari request, bukan generate random

            try {
                $pesanan = Pesanan::create([
                    'user_id'           => $user->id,
                    'paket_id'          => $request->paket_id,
                    'alamat'            => $request->alamat,
                    'catatan'           => $request->catatan,
                    'bukti_pembayaran'  => $path,
                    'nomor_struk'       => $nomorStruk,
                    'status'            => 'pending',
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal membuat pesanan: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal membuat pesanan: ' . $e->getMessage()], 500);
            }

            $paket = $pesanan->paketHarga;
            if (!$paket) {
                Log::error('Relasi paketHarga tidak ditemukan untuk pesanan ID: ' . $pesanan->id);
                return response()->json(['message' => 'Data paket tidak ditemukan.'], 500);
            }

            try {
                $pdf = Pdf::loadView('pdf.struk', [
                    'pesanan' => $pesanan,
                    'paket' => $paket,
                    'user' => $user,
                    'tanggal' => now()->format('d F Y'),
                    'waktu' => now()->format('H.i.s')
                ])
                ->setPaper([0, 0, 360, 500]);  // custom ukuran kertas
                $namaPelanggan = str_replace(' ', '_', $user->name);
                return $pdf->download("Pemesanan_Alat_IoT_-_$namaPelanggan.pdf");
            } catch (\Exception $e) {
                Log::error('Gagal membuat PDF: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal membuat struk PDF: ' . $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error membuat pesanan: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
