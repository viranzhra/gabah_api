<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KontakInfo;
use App\Models\PesanUser;
use Yajra\DataTables\Facades\DataTables;

class KontakController extends Controller
{
    public function destroyPesanUser($id) {
        $pesan = PesanUser::findOrFail($id);
        $pesan->delete();
        return response()->json(['message' => 'Pesan dihapus.']);
    }

    public function updateStatusPesanUser(Request $request, $id) {
        $request->validate(['status' => 'required|in:pending,replied']);
        $pesan = PesanUser::findOrFail($id);
        $pesan->status = $request->status;
        $pesan->save();
        return response()->json(['message' => 'Status diperbarui.']);
    }

    // Menampilkan data kontak info (anggap hanya 1 row)
    public function showContactInfo()
    {
        // Ambil kontak pertama (biasanya 1 row saja)
        $contact = KontakInfo::first();

        if (!$contact) {
            return response()->json([
                'message' => 'Data kontak tidak ditemukan.'
            ], 404);
        }

        return response()->json($contact);
    }

    // Update data kontak info
    public function updateContactInfo(Request $request)
    {
        $request->validate([
            'alamat' => 'required|string',
            'telepon' => 'required|string|max:20',
            'email' => 'required|email|max:100',
        ]);

        $contact = KontakInfo::first();
        if (!$contact) {
            return response()->json(['message' => 'Data kontak tidak ditemukan.'], 404);
        }

        $contact->alamat = $request->alamat;
        $contact->telepon = $request->telepon;
        $contact->email = $request->email;
        $contact->save();

        return response()->json([
            'message' => 'Data kontak berhasil diperbarui.',
            'data' => $contact
        ]);
    }

    // Menampilkan data pesan user untuk DataTables (AJAX)
    public function listPesanUser(Request $request)
    {
        $query = PesanUser::query()->orderBy('created_at', 'desc');

        // HAPUS blok Forbidden:
        // if (!$request->ajax()) {
        //     return response()->json(['message' => 'Forbidden'], 403);
        // }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('d-m-Y H:i');
            })
            ->make(true);
    }


    public function storePesanUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'message' => 'required|string',
        ]);

        $pesan = PesanUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'message' => $request->message,
        ]);

        // Bisa tambahkan kirim email notifikasi ke admin di sini (optional)

        return response()->json([
            'message' => 'Pesan Anda telah terkirim. Terima kasih!'
        ], 201);
    }
}
