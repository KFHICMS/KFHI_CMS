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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrController extends Controller
{
    // POST /api/children/{child}/qr
    public function generate(Request $request, Child $child)
    {
        // Revoke previous tokens
        QrToken::where('child_id', $child->id)->update(['is_active' => false]);

        // Random reference (no personal data)
        $reference = Str::random(40);
        QrToken::create([
            'child_id'  => $child->id,
            'token'     => $reference,
            'is_active' => true,
        ]);

        // Encrypt it, then wrap it in the scan URL the phone will open
        $encrypted = Crypt::encryptString($reference);
        $scanUrl   = rtrim(config('app.scan_url'), '/') . '?t=' . urlencode($encrypted);

        // Build a scannable QR image (SVG) from that URL
        $svg     = QrCode::format('svg')->size(260)->margin(1)->generate($scanUrl);
        $qrImage = 'data:image/svg+xml;base64,' . base64_encode($svg);

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
            'full_name'  => $child->full_name,   // ← NEW (for the printable ID card later)
            'program'    => $child->program,     // ← NEW (for the printable ID card later)
            'scan_url'   => $scanUrl,            // what the QR encodes
            'qr_image'   => $qrImage,            // the scannable QR image (SVG data URI)
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

        $child = $qr->child?->load('guardians');
        if (! $child) {
            $this->logFail($request, 'QR token without a child record');
            return response()->json(['message' => 'QR code is invalid or revoked'], 404);
        }

        // ← NEW: archived children's cards no longer resolve
        if ($child->status !== 'active') {
            $this->logFail($request, 'QR scanned for archived child #' . $child->id);
            return response()->json(['message' => 'This child record is archived.'], 410);
        }

        // ← NEW: field roles may only reach children in their assigned programs
        if (! $child->isAccessibleTo($request->user())) {
            $this->logFail($request, 'Out-of-scope scan for child #' . $child->id);
            return response()->json(['message' => 'This child is outside your assigned programs.'], 403);
        }

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