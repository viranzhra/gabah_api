<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index($processId)
    {
        \Log::info("Fetching notifications for process_id: {$processId}");
        $notifications = DB::table('notifications')
            ->where('process_id', $processId)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $notifications], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'process_id' => 'required|exists:drying_process,process_id',
            'message' => 'required|string',
        ]);

        \Log::info("Saving notification for process_id: {$data['process_id']}");
        DB::table('notifications')->insert([
            'process_id' => $data['process_id'],
            'message' => $data['message'],
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Notification saved'], 201);
    }
}