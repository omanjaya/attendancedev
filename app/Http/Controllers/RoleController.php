<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function getData()
    {
        $roles = Role::withCount('permissions')->with('users')->get();

        return response()->json([
            'data' => $roles->map(function ($role) {
                $actions = '';
                if ($role->name !== 'superadmin') {
                    $actions = '<button class="btn btn-sm btn-danger delete-role" data-role-id="'.$role->id.'">Delete</button>';
                }

                return [
                    'id' => $role->id,
                    'name' => ucfirst($role->name),
                    'permissions_count' => $role->permissions_count.' permissions',
                    'users_count' => $role->users->count().' users',
                    'created_at' => $role->created_at->format('M j, Y'),
                    'actions' => $actions,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return response()->json([
            'message' => 'Role permissions updated successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy(Role $role)
    {
        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return response()->json(
                [
                    'error' => 'Cannot delete super-admin role',
                ],
                403,
            );
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }
}
