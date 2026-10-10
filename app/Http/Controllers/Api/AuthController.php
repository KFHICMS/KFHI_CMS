<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            AuditLog::create([
                'user_id'    => $user?->id,
                'action'     => 'LOGIN_FAILED',
                'ip_address' => $request->ip(),
                'details'    => 'Failed login for ' . $credentials['email'],
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'LOGIN_BLOCKED',
                'ip_address' => $request->ip(),
                'details'    => 'Login blocked: account is deactivated',
            ]);

            return response()->json([
                'message' => 'Your account is disabled. Please contact the administrator.',
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('auth-token')->plainTextToken;

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'LOGIN',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'must_change_password' => $user->must_change_password,
            ],
            'token' => $token,
        ]);
    }

    // GET /api/me
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'must_change_password' => $user->must_change_password,
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        AuditLog::create([
            'user_id'    => $request->user()->id,
            'action'     => 'LOGOUT',
            'ip_address' => $request->ip(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
