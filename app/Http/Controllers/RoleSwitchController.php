<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class RoleSwitchController extends Controller
{
    /**
     * Switch to a different role temporarily
     */
    public function switchRole(Request $request, $roleName)
    {
        $user = Auth::user();

        // Security check: Only superadmin can switch roles
        if (! $user->hasRole('superadmin')) {
            abort(403, 'Unauthorized to switch roles.');
        }

        // Validate role exists
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return redirect()->back()->with('error', 'Role tidak ditemukan.');
        }

        // Prevent switching to superadmin role
        if ($roleName === 'superadmin') {
            return redirect()->back()->with('error', 'Tidak dapat switch ke role superadmin.');
        }

        // Available roles for switching
        $allowedRoles = ['teacher', 'admin', 'staff'];
        if (! in_array($roleName, $allowedRoles)) {
            return redirect()->back()->with('error', 'Role tidak diizinkan untuk switching.');
        }

        // Store original role if not already switching
        if (! Session::has('original_role')) {
            Session::put('original_role', $user->roles->first()->name);
        }

        // Log the role switch
        Log::info('Role switch performed', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'original_role' => Session::get('original_role'),
            'switched_to_role' => $roleName,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Remove all current roles and assign new role
        $user->syncRoles([$roleName]);

        // Clear any cached role-related data
        cache()->forget("user_permissions_{$user->id}");
        cache()->forget("user_roles_{$user->id}");
        cache()->forget("navigation_{$user->id}_{$user->roles->first()->name}");

        // For AJAX requests, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Role berhasil diubah ke {$role->name}",
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', "Role berhasil diubah ke {$role->name}");
    }

    /**
     * Restore original role
     */
    public function restoreRole(Request $request)
    {
        $user = Auth::user();

        if (! Session::has('original_role')) {
            return redirect()->back()->with('error', 'Tidak ada role asli untuk dikembalikan.');
        }

        $originalRole = Session::get('original_role');

        // Security check: Only allow restore if original role was superadmin
        if ($originalRole !== 'superadmin') {
            Session::forget('original_role');

            return redirect()->back()->with('error', 'Unauthorized role restore attempt.');
        }

        // Log the role restoration
        Log::info('Role restored', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'current_role' => $user->roles->first()->name,
            'restored_to_role' => $originalRole,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Restore original role
        $user->syncRoles([$originalRole]);

        // Clear any cached role-related data
        cache()->forget("user_permissions_{$user->id}");
        cache()->forget("user_roles_{$user->id}");
        cache()->forget("navigation_{$user->id}_{$user->roles->first()->name}");

        // Clear session
        Session::forget('original_role');

        // For AJAX requests, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Role berhasil dikembalikan ke {$originalRole}",
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', "Role berhasil dikembalikan ke {$originalRole}");
    }

    /**
     * Get available roles for switching
     */
    public function getAvailableRoles()
    {
        $user = Auth::user();

        // Allow access if user is superadmin OR if they have original_role = superadmin
        if (! $user->hasRole('superadmin') && Session::get('original_role') !== 'superadmin') {
            abort(403);
        }

        $roles = [
            [
                'name' => 'teacher',
                'display_name' => 'Guru',
                'description' => 'Akses untuk mengajar dan mengelola kelas',
                'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                'color' => 'emerald',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Administrasi dan operasional harian',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'color' => 'blue',
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff/Pegawai',
                'description' => 'Dukungan dan layanan umum',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'color' => 'gray',
            ],
        ];

        return response()->json(['roles' => $roles]);
    }
}
