<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        // $users = User::all();
        $users = User::with('roles')->get();

        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role->id] = $role->permissions->pluck('id')->toArray();
        }

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {
            $permissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
            $role->givePermissionTo($permissions);
        }

        return response()->json(['message' => 'Role created successfully'], 201);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return response()->json([
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'permissions' => 'array',
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);

        if ($request->permissions) {
            $permissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
            $role->syncPermissions($permissions);
        }

        return response()->json(['message' => 'Role updated successfully']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function getPermissions($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $allPermissions = Permission::all();

        return response()->json([
            'rolePermissions' => $role->permissions,
            'allPermissions' => $allPermissions,
        ]);
    }
}
