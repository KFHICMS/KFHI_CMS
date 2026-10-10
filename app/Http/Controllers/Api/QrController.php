<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\QrToken;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class QrController extends Controller
{
    // POST /api/children/{child}/qr  — generate (supersedes any old token)
    public function generate(Request $request, Child $child)
    {
        // Revoke previous tokens for this child
        QrToken::where('child_id', $child->id)->update(['is_active' => false]);

        // Random reference token — contains NO personal data
        $reference = Str::random(40);
        QrToken::create([
            'child_id'  => $child->id,
            'token'     => $reference,
            'is_active' => true,
        ]);

        // Encrypt it (AES-256 via APP_KEY). THIS string goes into the QR image.
        $encrypted = Crypt::encryptString($reference);

        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => 'GENERATE_QR',
            'entity_type' => 'Child',
            'entity_id'   => $child->id,
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'child_id'   => $child->id,
            'child_code' => $child->child_code,
            'qr_token'   => $encrypted,   // encode this into the QR code
        ]);
    }

    // POST /api/qr/resolve  { token }  — scan → authorized lookup
    public function resolve(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);

        // 1. Decrypt the token
        try {
            $reference = Crypt::decryptString($request->input('token'));
        } catch (DecryptException $e) {
            $this->logFail($request, 'Undecryptable QR token');
            return response()->json(['message' => 'Invalid QR code'], 422);
        }

        // 2. Look it up — must exist and be active (not revoked/superseded)
        $qr = QrToken::where('token', $reference)->where('is_active', true)->first();
        if (! $qr) {
            $this->logFail($request, 'Revoked or unknown QR token');
            return response()->json(['message' => 'QR code is invalid or revoked'], 404);
        }

        $child = $qr->child->load('guardians');

        // 3. Log the successful access
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => 'SCAN_QR',
            'entity_type' => 'Child',
            'entity_id'   => $child->id,
            'ip_address'  => $request->ip(),
        ]);

        // 4. Return ONLY the fields this user's role may see
        return response()->json($child->visibleTo($request->user()));
    }

    private function logFail(Request $request, string $reason): void
    {
        AuditLog::create([
            'user_id'    => $request->user()->id,
            'action'     => 'QR_RESOLVE_FAILED',
            'ip_address' => $request->ip(),
            'details'    => $reason,
        ]);
    }
}
