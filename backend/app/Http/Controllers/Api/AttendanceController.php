<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\QrToken;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    // POST /api/events/{event}/attendance/scan   { token }
    public function scan(Request $request, Event $event)
    {
        abort_unless($event->isAccessibleTo($request->user()), 403, 'You are not assigned to this event.');
        $request->validate(['token' => ['required', 'string']]);

        if ($event->status !== 'active' || ! $event->qr_attendance_enabled) {
            $this->audit($request, 'ATTENDANCE_SCAN_REFUSED', $event->id, 'Attendance closed for this event', 'Event');
            return response()->json(['message' => 'Attendance is closed for this event.'], 422);
        }

        // 1. Decrypt the QR token
        try {
            $reference = Crypt::decryptString($request->input('token'));
        } catch (DecryptException $e) {
            $this->audit($request, 'ATTENDANCE_SCAN_REFUSED', $event->id, 'Undecryptable QR token', 'Event');
            return response()->json(['message' => 'Invalid QR code'], 422);
        }

        // 2. Look up the child (the token must still be active)
        $qr    = QrToken::where('token', $reference)->where('is_active', true)->first();
        $child = $qr?->child;
        if (! $child) {
            $this->audit($request, 'ATTENDANCE_SCAN_REFUSED', $event->id, 'Revoked or unknown QR token', 'Event');
            return response()->json(['message' => 'QR code is invalid or revoked'], 404);
        }
        if ($child->status !== 'active') {
            $this->audit($request, 'ATTENDANCE_SCAN_REFUSED', $event->id, 'Archived child #' . $child->id, 'Event');
            return response()->json(['message' => 'This child record is archived.'], 410);
        }
        if (! $child->isAccessibleTo($request->user())) {
            $this->audit($request, 'ATTENDANCE_SCAN_REFUSED', $event->id, 'Out-of-scope child #' . $child->id, 'Event');
            return response()->json(['message' => 'This child is outside your assigned programs.'], 403);
        }

        // 3. Mark attendance. The unique (event_id, child_id) key is the real guard:
        //    if two scans arrive together, the loser is reported as a duplicate instead of crashing.
        $keys = ['event_id' => $event->id, 'child_id' => $child->id];
        try {
            $attendance    = Attendance::firstOrCreate($keys, ['status' => 'present', 'marked_by' => $request->user()->id]);
            $alreadyMarked = ! $attendance->wasRecentlyCreated;
        } catch (UniqueConstraintViolationException $e) {
            $attendance    = Attendance::where($keys)->firstOrFail();
            $alreadyMarked = true;
        }

        $this->audit(
            $request,
            $alreadyMarked ? 'DUPLICATE_ATTENDANCE_SCAN' : 'MARK_ATTENDANCE',
            $child->id,
            'Event #' . $event->id
        );

        // 4. Return ONLY child code + name (data minimisation)
        return response()->json([
            'child_code'     => $child->child_code,
            'child_name'     => $child->full_name,
            'status'         => $attendance->status,
            'already_marked' => $alreadyMarked,
            'message'        => $alreadyMarked
                ? 'Attendance has already been recorded for this child in this event.'
                : 'Attendance marked ✓',
        ]);
    }

    // GET /api/events/{event}/attendance   (view the list)
    public function index(Request $request, Event $event)
    {
        $user = $request->user();

        abort_unless($event->isAccessibleTo($user), 403, 'You are not assigned to this event.');

        $records = $event->attendances()
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
            'present' => $records->where('status', 'present')->count(),
            'total'   => $records->count(),
            'records' => $records,
        ]);
    }

    // GET /api/events/{event}/attendance/export   (CSV that opens in Excel)
    public function export(Request $request, Event $event)
    {
        $user = $request->user();

        abort_unless($event->isAccessibleTo($user), 403, 'You are not assigned to this event.');

        $rows = $event->attendances()
            ->whereHas('child', fn ($q) => $q->accessibleTo($user))
            ->with(['child:id,child_code,full_name', 'markedBy:id,name'])
            ->orderBy('created_at')
            ->get();

        $this->audit($request, 'EXPORT_ATTENDANCE', $event->id, $rows->count() . ' rows', 'Event');

        $filename = 'Attendance_' . (Str::slug($event->name, '_') ?: 'event') . '_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows non-English names correctly
            fputcsv($out, ['Child Code', 'Child Name', 'Status', 'Marked At', 'Marked By'], ',', '"', '\\');
            foreach ($rows as $a) {
                fputcsv($out, array_map([$this, 'safeCell'], [
                    $a->child->child_code,
                    $a->child->full_name,
                    $a->status,
                    optional($a->created_at)->format('Y-m-d H:i:s'),
                    $a->markedBy?->name,
                ]), ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheet formula injection: a cell starting with = + - @ gets a leading apostrophe. */
    private function safeCell($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function audit(Request $request, string $action, int $id, ?string $details = null, string $entity = 'Child'): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => $entity,
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
            'details'     => $details,
        ]);
    }
}