<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Check if user exists
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan catatan kami.'],
            ]);
        }

        // 2. Check if user is active
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini telah dinonaktifkan. Silakan hubungi administrator.'],
            ]);
        }

        // 3. Check if account is locked
        if ($user->isLocked()) {
            $lockTime = $user->locked_until ? $user->locked_until->diffForHumans() : 'indefinitely';
            throw ValidationException::withMessages([
                'email' => ["Akun terkunci. Silakan coba lagi {$lockTime}."],
            ]);
        }

        // 4. Verify password
        if (! Hash::check($request->password, $user->password)) {
            // Increment failed attempts and potentially lock account
            $user->incrementFailedLogins($request->ip());
            
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan catatan kami.'],
            ]);
        }

        // 5. Login successful - Update stats
        $user->updateLastLogin($request->ip());
        
        // 6. Create token
        $token = $user->createToken($request->device_name ?? 'web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['employee', 'roles', 'permissions']),
            'requires_password_change' => $user->needsPasswordChange(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['employee', 'roles', 'permissions']),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        // Update password and reset force_password_change flag
        $user->password = Hash::make($request->password);
        $user->force_password_change = false;
        $user->password_changed_at = now(); // Track when password was changed
        $user->save();

        // Revoke all tokens to force re-login
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login kembali.',
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Avatar berhasil diupload',
            'data' => [
                'avatar_url' => Storage::url($path),
            ],
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        // Delete avatar file if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Avatar berhasil dihapus',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password tidak valid.'],
            ]);
        }

        // Delete avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Delete all tokens
        $user->tokens()->delete();

        // Soft delete user (or force delete if needed)
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus',
        ]);
    }
}
