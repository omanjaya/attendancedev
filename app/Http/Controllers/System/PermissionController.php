<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    /**
     * Display the permission management interface.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Group permissions by category (first word before underscore)
            return explode('_', $permission->name)[0];
        });
        
        return view('pages.system.permissions.index', compact('roles', 'permissions'));
    }

    /**
     * Get roles data for DataTables.
     */
    public function getRolesData()
    {
        $roles = Role::withCount('users', 'permissions');
        
        return DataTables::of($roles)
            ->addColumn('permissions_count', function ($role) {
                return '<span class="badge bg-blue">' . $role->permissions_count . ' permissions</span>';
            })
            ->addColumn('users_count', function ($role) {
                return '<span class="badge bg-green">' . $role->users_count . ' users</span>';
            })
            ->addColumn('actions', function ($role) {
                if ($role->name === 'superadmin') {
                    return '<span class="text-muted">Protected</span>';
                }
                return '
                    <div class="btn-list">
                        <button class="btn btn-sm btn-primary edit-role" data-role-id="' . $role->id . '">
                            <svg class="icon" width="24" height="24"><use xlink:href="#edit"></use></svg>
                            Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-role" data-role-id="' . $role->id . '">
                            <svg class="icon" width="24" height="24"><use xlink:href="#trash"></use></svg>
                            Delete
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['permissions_count', 'users_count', 'actions'])
            ->make(true);
    }

    /**
     * Update role permissions.
     */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        // Don't allow editing superadmin permissions
        if ($role->name === 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify superadmin permissions'
            ], 403);
        }

        $role->syncPermissions($request->permissions ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully'
        ]);
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'role' => $role
        ]);
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role)
    {
        // Don't allow deleting protected roles
        if (in_array($role->name, ['superadmin', 'admin', 'teacher', 'staff'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system roles'
            ], 403);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users'
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}