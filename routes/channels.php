<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\DryerProcess;
use Illuminate\Support\Facades\Log;

Broadcast::channel('drying-process.{dryerId}', function($user, $dryerId){
    return true;
});


// Channel untuk dryer tertentu
Broadcast::channel('dryer.{dryerId}', function ($user, $dryerId) {
    return true;
});

// Broadcast::channel('drying-process.{processId}', function ($user, $processId) {

//     // Penanganan channel default
//     if ($processId === 'default') {
//         Log::info("User {$user->id} mengakses channel default drying-process.default");
//         // Izinkan akses ke channel default untuk pengguna yang login
//         return true;
//     }

//     // Cek apakah proses ada dan terkait dengan user
//     $process = DryerProcess::where('process_id', $processId)
//         ->whereHas('bedDryer', function ($query) use ($user) {
//             $query->where('user_id', $user->id); // Asumsi bed_dryers punya user_id
//         })
//         ->exists();

//     if ($process) {
//         Log::info("User {$user->id} berhasil mengakses channel drying-process.{$processId}");
//         return true;
//     }

//     Log::warning("User {$user->id} tidak memiliki akses ke proses ID {$processId}");
//     return false;
// });

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     $access = (int) $user->id === (int) $id;
//     Log::info("User channel authentication:", [
//         'user_id' => $user->id,
//         'channel_id' => $id,
//         'access' => $access,
//     ]);
//     return $access;
// });