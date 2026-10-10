<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    // POST /api/change-password
    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', 'different:current_password',
                Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        // The old password must be correct.
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $currentTokenId = $user->currentAccessToken()->id;

        DB::transaction(function () use ($request, $user, $data, $currentTokenId) {
            $user->password             = $data['password']; // auto-hashed by the model
            $user->must_change_password = false;
            $user->save();

            // Log out all OTHER sessions. Keep the one in use now.
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'PASSWORD_CHANGED',
                'entity_type' => 'User',
                'entity_id'   => $user->id,
                'ip_address'  => $request->ip(),
                'details'     => 'User changed own password',
            ]);
        });

        return response()->json(['message' => 'Password changed successfully.']);
    }
}