<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\KfhiTestCase;

class QrAttendanceTest extends KfhiTestCase
{
    private function openEvent(array $attrs = []): Event
    {
        return Event::create(array_merge([
            'name' => 'Health Day', 'status' => 'active',
            'qr_attendance_enabled' => true, 'has_questionnaire' => false,
        ], $attrs));
    }

    private function tokenFromScanUrl(string $scanUrl): string
    {
        parse_str((string) parse_url($scanUrl, PHP_URL_QUERY), $query);

        return $query['t'];
    }

    // ---------------- QR ----------------

    public function test_only_authorised_roles_can_generate_a_qr_code(): void
    {
        $child = $this->makeChild('Education');

        $this->asUser($this->makeUser('field_officer', ['Education']))
            ->postJson("/api/children/{$child->id}/qr")->assertForbidden();

        $this->asUser($this->makeUser('child_officer'))
            ->postJson("/api/children/{$child->id}/qr")
            ->assertOk()
            ->assertJsonStructure(['child_id', 'child_code', 'scan_url', 'qr_image']);
    }

    public function test_generated_qr_resolves_and_contains_no_child_data(): void
    {
        $child = $this->makeChild('Education', ['full_name' => 'Secret Name']);

        $res = $this->asUser($this->makeUser('child_officer'))
            ->postJson("/api/children/{$child->id}/qr")->assertOk();

        $this->assertStringNotContainsString('Secret Name', $res->json('scan_url'));
        $this->assertStringNotContainsString($child->child_code, $res->json('scan_url'));

        $token = $this->tokenFromScanUrl($res->json('scan_url'));

        $this->asUser($this->makeUser('field_officer', ['Education']))
            ->postJson('/api/qr/resolve', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('child_code', $child->child_code);
    }

    public function test_garbage_and_unknown_tokens_are_rejected(): void
    {
        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => 'not-a-real-token'])
            ->assertStatus(422);

        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => Crypt::encryptString('unknown-reference')])
            ->assertNotFound();
    }

    public function test_regenerating_a_qr_revokes_the_previous_one(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');

        $first  = $this->tokenFromScanUrl($this->asUser($officer)->postJson("/api/children/{$child->id}/qr")->json('scan_url'));
        $second = $this->tokenFromScanUrl($this->asUser($officer)->postJson("/api/children/{$child->id}/qr")->json('scan_url'));

        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => $first])->assertNotFound();
        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => $second])->assertOk();
    }

    public function test_archiving_a_child_stops_their_qr_working(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');
        $token   = $this->qrTokenFor($child);

        $this->asUser($officer)->patchJson("/api/children/{$child->id}/archive")->assertOk();

        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => $token])->assertNotFound();
    }

    public function test_archived_child_with_a_still_active_token_is_refused(): void
    {
        $child = $this->makeChild('Education');
        $token = $this->qrTokenFor($child);
        $child->update(['status' => 'archived']);

        $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/qr/resolve', ['token' => $token])->assertStatus(410);
    }

    public function test_scanning_a_child_outside_your_programs_is_forbidden_and_logged(): void
    {
        $child   = $this->makeChild('Health');
        $token   = $this->qrTokenFor($child);
        $officer = $this->makeUser('field_officer', ['Education']);

        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => $token])->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $officer->id, 'action' => 'QR_RESOLVE_FAILED']);
    }

    // ---------------- attendance ----------------

    public function test_enumerator_records_attendance_once_and_sees_only_code_and_name(): void
    {
        $event = $this->openEvent();
        $child = $this->makeChild('Education');
        $token = $this->qrTokenFor($child);
        $enum  = $this->makeUser('survey_enumerator', ['Education']);

        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertOk()
            ->assertJsonPath('child_code', $child->child_code)
            ->assertJsonPath('already_marked', false)
            ->assertJsonMissingPath('date_of_birth')
            ->assertJsonMissingPath('medical_info')
            ->assertJsonMissingPath('address');

        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertOk()
            ->assertJsonPath('already_marked', true);

        $this->assertSame(1, Attendance::count());
    }

    public function test_database_itself_rejects_duplicate_attendance(): void
    {
        $event = $this->openEvent();
        $child = $this->makeChild('Education');
        $row = ['event_id' => $event->id, 'child_id' => $child->id, 'status' => 'present',
                'created_at' => now(), 'updated_at' => now()];

        DB::table('attendances')->insert($row);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('attendances')->insert($row);
    }

    public function test_attendance_is_refused_when_event_is_closed_or_attendance_is_off(): void
    {
        $child = $this->makeChild('Education');
        $token = $this->qrTokenFor($child);
        $enum  = $this->makeUser('survey_enumerator', ['Education']);

        $completed = $this->openEvent(['status' => 'completed']);
        $this->asUser($enum)->postJson("/api/events/{$completed->id}/attendance/scan", ['token' => $token])
            ->assertStatus(422);

        $off = $this->openEvent(['name' => 'No QR', 'qr_attendance_enabled' => false]);
        $this->asUser($enum)->postJson("/api/events/{$off->id}/attendance/scan", ['token' => $token])
            ->assertStatus(422);

        $this->assertSame(0, Attendance::count());
    }

    public function test_roles_without_attendance_permission_are_refused(): void
    {
        $event = $this->openEvent();
        $token = $this->qrTokenFor($this->makeChild('Education'));

        $this->asUser($this->makeUser('child_officer'))
            ->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_attendance_for_a_child_outside_your_programs_or_archived_is_refused(): void
    {
        $event = $this->openEvent();
        $enum  = $this->makeUser('survey_enumerator', ['Education']);

        $outside = $this->makeChild('Health');
        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $this->qrTokenFor($outside)])
            ->assertForbidden();

        $archived = $this->makeChild('Education');
        $token = $this->qrTokenFor($archived);
        $archived->update(['status' => 'archived']);
        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertStatus(410);

        $this->assertSame(0, Attendance::count());
    }

    public function test_attendance_list_only_shows_children_the_user_may_access(): void
    {
        $event = $this->openEvent();
        $edu   = $this->makeChild('Education');
        $hlt   = $this->makeChild('Health');

        foreach ([$edu, $hlt] as $c) {
            DB::table('attendances')->insert([
                'event_id' => $event->id, 'child_id' => $c->id, 'status' => 'present',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->asUser($this->makeUser('field_officer', ['Education']))
            ->getJson("/api/events/{$event->id}/attendance")
            ->assertOk()->assertJsonPath('present', 1)->assertJsonCount(1, 'records');

        $this->asUser($this->makeUser('admin'))
            ->getJson("/api/events/{$event->id}/attendance")
            ->assertOk()->assertJsonPath('present', 2);
    }
}