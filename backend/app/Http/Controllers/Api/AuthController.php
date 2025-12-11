<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable',
            'turnstile_token' => 'required|string',
        ]);

        // Verify Cloudflare Turnstile token
        $turnstileSecret = env('TURNSTILE_SECRET_KEY');
        if ($turnstileSecret) {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $turnstileSecret,
                'response' => $request->turnstile_token,
                'remoteip' => $request->ip(),
            ]);

            if (!$response->successful() || !$response->json('success')) {
                throw ValidationException::withMessages([
                    'turnstile' => ['Verifikasi keamanan gagal. Silakan coba lagi.'],
                ]);
            }
        }

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

    /**
     * Handle a forgot password request (send reset link email).
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Always return success message to prevent user enumeration
        // Even if email doesn't exist, we show the same message
        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return response()->json([
                'success' => true,
                'message' => 'Jika email terdaftar di sistem kami, Anda akan menerima link reset password.',
            ]);
        }

        // Only show error for rate limiting (throttle)
        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Silakan tunggu beberapa menit.',
            ], 429);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses permintaan. Silakan coba lagi.',
        ], 400);
    }

    /**
     * Handle password reset (verify token and update password).
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Attempt to reset the user's password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'password_changed_at' => now(),
                    'force_password_change' => false,
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.',
            ]);
        }

        // Handle different error statuses
        $messages = [
            Password::INVALID_USER => 'Email tidak ditemukan.',
            Password::INVALID_TOKEN => 'Token reset password tidak valid atau sudah kadaluarsa.',
        ];

        return response()->json([
            'success' => false,
            'message' => $messages[$status] ?? 'Gagal mereset password.',
        ], 400);
    }
}
