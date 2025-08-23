<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Menampilkan daftar roles, users, dan permissions.
     */
    public function index()
{
    try {
        $roles = Role::with('permissions')->get(); // eager load
        $users = User::with('roles', 'permissions')->get(); // load permissions juga
        $permissions = Permission::all();

        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->id] = $role->permissions->pluck('id')->toArray();
        }

        return response()->json([
            'roles' => $roles,
            'users' => $users,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching roles', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Terjadi kesalahan saat memuat data.'], 500);
    }
}

    /**
     * Menyimpan role baru.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|unique:roles,name',
                'guard_name' => 'nullable|string', // Ubah ke nullable
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            // Buat role baru dengan guard_name default 'web' jika tidak ada
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web', // Default ke 'web'
            ]);

            // Jika ada permissions, sinkronkan
            if ($request->has('permissions') && !empty($request->permissions)) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'message' => 'Role berhasil ditambahkan.',
                'role' => $role,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error storing role:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error storing role:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'SQLSTATE[23502]')) {
                $errorMessage = 'Data role tidak lengkap (guard_name hilang). Silakan coba lagi.';
            }
            return response()->json(['error' => 'Gagal menambahkan role: ' . $errorMessage], 500);
        }
    }

    /**
     * Menampilkan detail role untuk edit.
     */
    public function edit($id)
    {
        try {
            $role = Role::findOrFail($id);
            $permissions = Permission::all();
            $rolePermissions = $role->permissions ? $role->permissions->pluck('id')->toArray() : [];

            return response()->json([
                'role' => $role,
                'permissions' => $permissions,
                'role_permissions' => $rolePermissions,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching role', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal memuat data role.'], 500);
        }
    }

    /**
     * Memperbarui role.
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|unique:roles,name,' . $id,
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $role = Role::findOrFail($id);
            $role->update(['name' => $request->name]);
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'message' => 'Role berhasil diperbarui.',
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating role', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal memperbarui role: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus role.
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json(['message' => 'Role berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Error deleting role', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal menghapus role.'], 500);
        }
    }

    /**
     * Mengambil permissions untuk role tertentu.
     */
    public function getPermissions($id)
    {
        try {
            $role = Role::findOrFail($id);
            $permissions = $role->permissions ? $role->permissions : collect([]);

            return response()->json(['permissions' => $permissions]);
        } catch (\Exception $e) {
            Log::error('Error fetching permissions', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal memuat data permissions.'], 500);
        }
    }

    /**
     * Menampilkan detail user.
     */
    public function showUser($id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);
            return response()->json([
                'user_role' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->roles->first() ? $user->roles->first()->id : null,
                    'role_name' => $user->roles->first() ? $user->roles->first()->name : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal memuat data user.'], 500);
        }
    }

    /**
     * Store user.
     */
    public function storeUser(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $role = Role::findOrFail($request->role_id);
            $user->assignRole($role);

            return response()->json([
                'message' => 'User berhasil ditambahkan.',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing user', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal menambahkan user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update user.
     */
    public function updateUser(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            $user = User::findOrFail($id);
            $user->update(['name' => $request->name]);

            $role = Role::findOrFail($request->role_id);
            $user->syncRoles($role);

            return response()->json([
                'message' => 'User berhasil diperbarui.',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating user', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal memperbarui user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete user.
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json(['message' => 'User berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Error deleting user', ['id' => $id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal menghapus user.'], 500);
        }
    }
}