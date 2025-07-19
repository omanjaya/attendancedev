<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user
     */
    public function start(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // Security check: Only users with impersonate permission can impersonate
        if (! $currentUser->can('impersonate_users')) {
            abort(403, 'Unauthorized to impersonate users.');
        }

        // Prevent impersonating someone with higher privileges
        if ($user->hasRole('Super Admin') && ! $currentUser->hasRole('Super Admin')) {
            abort(403, 'Cannot impersonate Super Admin.');
        }

        // Cannot impersonate yourself
        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'Tidak dapat login sebagai diri sendiri.');
        }

        // Cannot impersonate if already impersonating
        if (Session::has('impersonated_by')) {
            return redirect()->back()->with('error', 'Sudah dalam mode impersonation.');
        }

        // Log the impersonation activity
        Log::info('User impersonation started', [
            'original_user_id' => $currentUser->id,
            'original_user_name' => $currentUser->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Store original user ID in session
        Session::put('impersonated_by', $currentUser->id);

        // Log in as the target user
        Auth::login($user);

        // Clear any cached data for both users
        cache()->forget("user_permissions_{$currentUser->id}");
        cache()->forget("user_roles_{$currentUser->id}");
        cache()->forget("user_permissions_{$user->id}");
        cache()->forget("user_roles_{$user->id}");

        // For AJAX requests, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Sekarang login sebagai {$user->name}",
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', "Sekarang login sebagai {$user->name}");
    }

    /**
     * Stop impersonating and return to original user
     */
    public function stop(Request $request)
    {
        if (! Session::has('impersonated_by')) {
            return redirect()->back()->with('error', 'Tidak dalam mode impersonation.');
        }

        $originalUserId = Session::get('impersonated_by');
        $impersonatedUser = Auth::user();

        // Find original user
        $originalUser = User::find($originalUserId);

        if (! $originalUser) {
            Session::forget('impersonated_by');
            Auth::logout();

            return redirect()->route('login')->with('error', 'Original user not found. Please login again.');
        }

        // Log the end of impersonation
        Log::info('User impersonation ended', [
            'original_user_id' => $originalUser->id,
            'original_user_name' => $originalUser->name,
            'impersonated_user_id' => $impersonatedUser->id,
            'impersonated_user_name' => $impersonatedUser->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Remove impersonation session
        Session::forget('impersonated_by');

        // Log back in as original user
        Auth::login($originalUser);

        // Clear any cached data for both users
        cache()->forget("user_permissions_{$originalUser->id}");
        cache()->forget("user_roles_{$originalUser->id}");
        cache()->forget("user_permissions_{$impersonatedUser->id}");
        cache()->forget("user_roles_{$impersonatedUser->id}");

        // For AJAX requests, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Kembali login sebagai {$originalUser->name}",
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard')->with('success', "Kembali login sebagai {$originalUser->name}");
    }

    /**
     * Get list of users that can be impersonated
     */
    public function getUserList(Request $request)
    {
        $currentUser = Auth::user();

        // Security check
        if (! $currentUser->can('impersonate_users')) {
            abort(403);
        }

        // Build query based on permissions
        $query = User::with('roles')
            ->where('id', '!=', $currentUser->id) // Exclude current user
            ->where('is_active', true);

        // Super Admin can impersonate anyone except other Super Admins
        // Admin can only impersonate Teachers and Staff
        if ($currentUser->hasRole('Super Admin')) {
            // Super Admin can impersonate everyone except other Super Admins
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Super Admin');
            });
        } else {
            // Admin can only impersonate Teachers and Staff
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Teacher', 'Staff']);
            });
        }

        // Add search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Add role filter
        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
