<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Attendance;
use App\Models\QrToken;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class AttendanceController extends Controller
{
    // POST /api/events/{event}/attendance/scan   { token }
    public function scan(Request $request, Event $event)
    {
        $request->validate(['token' => ['required', 'string']]);
                if ($event->status !== 'active' || ! $event->qr_attendance_enabled) {
            return response()->json(['message' => 'Attendance is closed for this event.'], 422);
        }

        // 1. Decrypt the QR token
        try {
            $reference = Crypt::decryptString($request->input('token'));
        } catch (DecryptException $e) {
            return response()->json(['message' => 'Invalid QR code'], 422);
        }

        // 2. Look up the child (must be an active token)
        $qr = QrToken::where('token', $reference)->where('is_active', true)->first();
        if (! $qr) {
            return response()->json(['message' => 'QR code is invalid or revoked'], 404);
        }
        $child = $qr->child;
                if ($child->status !== 'active') {
            return response()->json(['message' => 'This child record is archived.'], 410);
        }
        if (! $child->isAccessibleTo($request->user())) {
            return response()->json(['message' => 'This child is outside your assigned programs.'], 403);
        }

        // 3. Mark attendance — firstOrCreate prevents duplicates
        $attendance = Attendance::firstOrCreate(
            ['event_id' => $event->id, 'child_id' => $child->id],
            ['status' => 'present', 'marked_by' => $request->user()->id]
        );
        $alreadyMarked = ! $attendance->wasRecentlyCreated;

        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => 'MARK_ATTENDANCE',
            'entity_type' => 'Child',
            'entity_id'   => $child->id,
            'ip_address'  => $request->ip(),
        ]);

        // 4. Return ONLY child code + name (data minimization per your spec)
        return response()->json([
            'child_code'     => $child->child_code,
            'child_name'     => $child->full_name,
            'status'         => 'present',
            'already_marked' => $alreadyMarked,
            'message'        => $alreadyMarked ? 'Already marked present' : 'Attendance marked ✓',
        ]);
    }

    // GET /api/events/{event}/attendance   (view the list)
public function index(Request $request, Event $event)
{
    $user = $request->user();

    $list = $event->attendances()
        ->whereHas('child', fn ($q) => $q->accessibleTo($user))
        ->with('child:id,child_code,full_name')
        ->latest()->get()
        ->map(fn ($a) => [
            'child_code' => $a->child->child_code,
            'child_name' => $a->child->full_name,
            'status'     => $a->status,
            'marked_at'  => $a->created_at,
        ]);

    return response()->json([
        'event'   => $event->name,
        'present' => $list->count(),
        'records' => $list,
    ]);
}
}
