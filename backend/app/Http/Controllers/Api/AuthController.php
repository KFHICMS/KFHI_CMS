<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

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

        // Check email/password
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

        // Check if account is active
        if (! $user->is_active) {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'LOGIN_BLOCKED_INACTIVE',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Your account is deactivated.',
            ], 403);
        }

        // Create authentication token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log successful login
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
                'username'    => $user->username,
                'must_change_password' => (bool) $user->must_change_password,
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
            'username'    => $user->username,
            'must_change_password' => (bool) $user->must_change_password,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
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

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

        // POST /api/change-password
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', 'different:current_password',
                                   Password::min(8)->mixedCase()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'PASSWORD_CHANGE_FAILED',
                'entity_type' => 'User',
                'entity_id'   => $user->id,
                'ip_address'  => $request->ip(),
                'details'     => 'Wrong current password',
            ]);
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password'             => $data['password'],
            'must_change_password' => false,
            'password_changed_at'  => now(),
        ])->save();

        // sign out all other sessions, keep this one
        $current = $user->currentAccessToken();
        $others  = $user->tokens();
        if ($current && isset($current->id)) {
            $others = $others->where('id', '!=', $current->id);
        }
        $others->delete();

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'PASSWORD_CHANGED',
            'entity_type' => 'User',
            'entity_id'   => $user->id,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json(['message' => 'Password changed.']);
    }
}