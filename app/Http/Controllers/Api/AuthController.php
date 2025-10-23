<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\BedDryer;
use App\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        Log::info('API Registration Attempt', ['email' => $request->email]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign default role 'Admin Mitra'
            $role = Role::findByName('Admin Mitra');
            if (!$role) {
                Log::error('API Registration Failed: Role Admin Mitra not found', ['email' => $request->email]);
                return response()->json([
                    'status' => 'error',
                    'error' => 'Role Admin Mitra tidak ditemukan',
                ], 500);
            }
            
            $user->assignRole($role);
            $token = $user->createToken('web-token', ['web'])->plainTextToken;

            Log::info('API Registration Success', ['email' => $request->email, 'role' => 'Admin Mitra']);

            return response()->json([
                'status' => 'success',
                'message' => "Registrasi berhasil.\nSilahkan Login sesuai akun Anda!",
                'data' => [
                    'token' => $token,
                    'role' => 'Admin Mitra',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Registration Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email
            ]);
            
            return response()->json([
                'status' => 'error',
                'error' => 'Terjadi kesalahan saat registrasi',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        Log::info('API Login Attempt', ['email' => $request->email]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('API Login Failed: Invalid credentials', ['email' => $request->email]);
            return response()->json([
                'status' => 'error',
                'error' => 'Email atau password salah',
            ], 401);
        }

        $role = $user->getRoleNames()->first();
        if (!$role) {
            Log::error('API Login Failed: User has no role', ['email' => $request->email]);
            return response()->json([
                'status' => 'error',
                'error' => 'Pengguna tidak memiliki peran yang ditetapkan',
            ], 403);
        }

        $token = $user->createToken('web-token', ['web'])->plainTextToken;
        $role = ucfirst($role);

        Log::info('API Login Success', ['email' => $request->email, 'role' => $role]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'role' => $role,
            ],
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function myBedDryers(Request $request)
    {
        $user = $request->user();

        $dryers = BedDryer::with([
                'warehouse:warehouse_id,nama',
                'devices:device_id,dryer_id,device_name,address,location,status'
            ])
            ->where('user_id', $user->id)
            ->orderBy('nama')
            ->get();

        $payload = $dryers->map(function ($d) {
            return [
                'dryer_id'        => $d->dryer_id,
                'nama'            => $d->nama,
                // lokasi dari warehouses.nama
                'lokasi'          => optional($d->warehouse)->nama,
                'deskripsi'       => $d->deskripsi,
                'sensor_devices'  => $d->devices->map(function ($dev) {
                    return [
                        'device_id'   => $dev->device_id,
                        'device_name' => $dev->device_name,
                        'address'     => $dev->address,
                        'location'     => $dev->location,
                        'status'      => (bool) $dev->status,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($payload);
    }

    public function logout(Request $request)
    {
        if (!$request->user()) {
            Log::warning('API Logout Failed: No authenticated user');
            return response()->json([
                'status' => 'error',
                'error' => 'Tidak ada pengguna yang terautentikasi',
            ], 401);
        }

        Log::info('API Logout Attempt', ['user_id' => $request->user()->id]);
        $request->user()->currentAccessToken()->delete();
        Log::info('API Logout Success', ['user_id' => $request->user()->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ], 200);
    }

    public function user(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('API User Fetch Failed: No authenticated user');
                return response()->json([
                    'status' => 'error',
                    'error' => 'Tidak ada pengguna yang terautentikasi',
                ], 401);
            }

            Log::info('API User Fetch Success', ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengguna berhasil diambil.',
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => ucfirst($user->getRoleNames()->first() ?? 'No role'),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('API User Fetch Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'error' => 'Terjadi kesalahan saat mengambil data pengguna',
            ], 500);
        }
    }
}
