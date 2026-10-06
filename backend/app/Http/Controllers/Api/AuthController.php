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
}