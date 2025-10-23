<?php

namespace App\Http\Controllers\Api_mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppNotification;

class MobileNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $items = AppNotification::where('user_id', $user->id)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'title'      => $n->title,
                    'body'       => $n->body,
                    'dryer_id'   => $n->dryer_id,
                    'process_id' => $n->process_id,
                    'dryer_name' => null,
                    'created_at' => $n->created_at->toISOString(),
                ];
            });

        return response()->json(['data' => $items]);
    }

    // NEW: hapus 1 notifikasi milik user
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $notif = AppNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notif) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);
        }

        $notif->delete();

        // Kembalikan sukses (tanpa body)
        return response()->noContent(); // 204
    }
}
