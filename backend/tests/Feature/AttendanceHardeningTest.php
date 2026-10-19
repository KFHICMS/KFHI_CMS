<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Support\Facades\Crypt;
use Tests\KfhiTestCase;

class AttendanceHardeningTest extends KfhiTestCase
{
    private function openEvent(): Event
    {
        return Event::create([
            'name' => 'Health Day', 'status' => 'active',
            'qr_attendance_enabled' => true, 'has_questionnaire' => false,
        ]);
    }

    /** The QR token a card would carry, made through the real endpoint. */
    private function tokenFor($child): string
    {
        $res = $this->asUser($this->makeUser('admin'))->postJson("/api/children/{$child->id}/qr")->assertOk();
        parse_str(parse_url($res->json('scan_url'), PHP_URL_QUERY), $query);

        return $query['t'];
    }

    public function test_duplicate_scan_creates_one_row_and_says_so_clearly(): void
    {
        $event = $this->openEvent();
        $child = $this->makeChild('Education');
        $token = $this->tokenFor($child);
        $user  = $this->makeUser('survey_enumerator', ['Education']);
        $event->users()->attach($user->id);

        $this->asUser($user)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertOk()->assertJsonPath('already_marked', false);

        $this->asUser($user)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertOk()
            ->assertJsonPath('already_marked', true)
            ->assertJsonPath('message', 'Attendance has already been recorded for this child in this event.');

        $this->assertSame(1, Attendance::where('event_id', $event->id)->count());
        $this->assertSame(1, AuditLog::where('action', 'MARK_ATTENDANCE')->count());
        $this->assertSame(1, AuditLog::where('action', 'DUPLICATE_ATTENDANCE_SCAN')->count());
    }

    public function test_refused_scans_are_audited(): void
    {
        $event = $this->openEvent();
        $user  = $this->makeUser('survey_enumerator', ['Education']);
        $event->users()->attach($user->id);

        $this->asUser($user)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => 'garbage'])->assertStatus(422);
        $this->asUser($user)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => Crypt::encryptString('unknown')])->assertNotFound();

        $this->assertSame(2, AuditLog::where('action', 'ATTENDANCE_SCAN_REFUSED')->count());
        $this->assertSame(0, Attendance::count());
    }

    public function test_admin_exports_a_safe_csv_and_exporting_creates_nothing(): void
    {
        $admin = $this->makeUser('admin');
        $event = $this->openEvent();
        $evil  = $this->makeChild('Education', ['full_name' => '=HYPERLINK("http://evil.test")']);
        $good  = $this->makeChild('Education', ['full_name' => 'Nimal Perera']);

        foreach ([$evil, $good] as $c) {
            Attendance::create(['event_id' => $event->id, 'child_id' => $c->id, 'status' => 'present', 'marked_by' => $admin->id]);
        }
        $before = Attendance::count();

        $res = $this->asUser($admin)->get("/api/events/{$event->id}/attendance/export");
        $res->assertOk();
        $this->assertStringContainsString('Attendance_health_day_', $res->headers->get('content-disposition'));

        $csv = $res->streamedContent();
        $this->assertStringContainsString('"Child Code","Child Name",Status,"Marked At","Marked By"', $csv);
        $this->assertStringContainsString($good->child_code, $csv);
        $this->assertStringContainsString('Nimal Perera', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);        // formula neutralised
        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);

        $this->assertSame($before, Attendance::count());
        $this->assertSame(1, AuditLog::where('action', 'EXPORT_ATTENDANCE')->count());
    }

    public function test_roles_without_report_permission_cannot_export(): void
    {
        $event = $this->openEvent();

        foreach (['field_officer', 'survey_enumerator'] as $role) {
            $user = $this->makeUser($role, ['Education']);
            $event->users()->attach($user->id);
            $this->asUser($user)->get("/api/events/{$event->id}/attendance/export")->assertForbidden();
        }
    }
}