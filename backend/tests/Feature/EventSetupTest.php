<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\KfhiTestCase;

class EventSetupTest extends KfhiTestCase
{
    public function test_admin_can_create_an_event_with_program_times_location_and_assignees(): void
    {
        $program = $this->makeProgram('Education');
        $enum    = $this->makeUser('survey_enumerator', ['Education']);
        $officer = $this->makeUser('field_officer');

        $this->asUser($this->makeUser('admin'))->postJson('/api/events', [
            'name'                  => 'Health Awareness Day',
            'description'           => 'Community health session',
            'program_id'            => $program->id,
            'start_at'              => '2026-10-20 09:00:00',
            'end_at'                => '2026-10-20 13:00:00',
            'location'              => 'Galle Town Hall',
            'qr_attendance_enabled' => true,
            'has_questionnaire'     => true,
            'user_ids'              => [$enum->id, $officer->id],
        ])
            ->assertCreated()
            ->assertJsonPath('program.name', 'Education')
            ->assertJsonPath('location', 'Galle Town Hall')
            ->assertJsonPath('qr_attendance_enabled', true)
            ->assertJsonPath('has_questionnaire', true)
            ->assertJsonCount(2, 'user_ids');

        $this->assertDatabaseCount('event_user', 2);
    }

    public function test_attendance_and_questionnaire_are_off_unless_the_admin_switches_them_on(): void
    {
        $this->asUser($this->makeUser('admin'))->postJson('/api/events', ['name' => 'Plain event'])
            ->assertCreated()
            ->assertJsonPath('qr_attendance_enabled', false)
            ->assertJsonPath('has_questionnaire', false);
    }

    public function test_end_must_not_be_before_start(): void
    {
        $this->asUser($this->makeUser('admin'))->postJson('/api/events', [
            'name' => 'Bad dates', 'start_at' => '2026-10-20 13:00:00', 'end_at' => '2026-10-20 09:00:00',
        ])->assertStatus(422)->assertJsonValidationErrors('end_at');
    }

    public function test_end_is_checked_against_the_stored_start_on_update(): void
    {
        $event = Event::create(['name' => 'E', 'status' => 'active', 'start_at' => '2026-10-20 09:00:00']);

        $this->asUser($this->makeUser('admin'))
            ->putJson("/api/events/{$event->id}", ['end_at' => '2026-10-19 09:00:00'])
            ->assertStatus(422)->assertJsonValidationErrors('end_at');
    }

    public function test_unknown_program_is_rejected(): void
    {
        $this->asUser($this->makeUser('admin'))
            ->postJson('/api/events', ['name' => 'X', 'program_id' => 99999])
            ->assertStatus(422)->assertJsonValidationErrors('program_id');
    }

    public function test_only_active_officers_and_enumerators_can_be_assigned(): void
    {
        $admin    = $this->makeUser('admin');
        $other    = $this->makeUser('admin');
        $inactive = $this->makeUser('survey_enumerator', [], ['is_active' => false]);

        $this->asUser($admin)->postJson('/api/events', ['name' => 'X', 'user_ids' => [$other->id]])
            ->assertStatus(422)->assertJsonValidationErrors('user_ids');

        $this->asUser($admin)->postJson('/api/events', ['name' => 'X', 'user_ids' => [$inactive->id]])
            ->assertStatus(422)->assertJsonValidationErrors('user_ids');

        $this->assertSame(0, Event::count());
    }

    public function test_a_user_with_manage_programs_but_not_manage_event_options_cannot_touch_the_switches(): void
    {
        $helper = $this->makeUser('child_officer');
        $helper->givePermissionTo('manage_programs');

        // cannot create an event with the switches on
        $this->asUser($helper)->postJson('/api/events', ['name' => 'X', 'qr_attendance_enabled' => true])->assertForbidden();
        $this->assertSame(0, Event::count());

        // can create a normal event; the switches stay off
        $this->asUser($helper)->postJson('/api/events', ['name' => 'Normal'])
            ->assertCreated()->assertJsonPath('qr_attendance_enabled', false);

        $event = Event::firstOrFail();

        // cannot flip them later either…
        $this->asUser($helper)->putJson("/api/events/{$event->id}", ['has_questionnaire' => true])->assertForbidden();
        $this->assertFalse($event->fresh()->has_questionnaire);

        // …but can still edit ordinary details
        $this->asUser($helper)->putJson("/api/events/{$event->id}", ['name' => 'Renamed', 'location' => 'Hall'])
            ->assertOk()->assertJsonPath('name', 'Renamed');
    }

    public function test_admin_switching_options_is_recorded_in_the_audit_log(): void
    {
        $admin = $this->makeUser('admin');
        $event = Event::create(['name' => 'E', 'status' => 'active',
                                'qr_attendance_enabled' => false, 'has_questionnaire' => false]);

        $this->asUser($admin)->putJson("/api/events/{$event->id}", ['qr_attendance_enabled' => true, 'has_questionnaire' => true])
            ->assertOk()
            ->assertJsonPath('qr_attendance_enabled', true)
            ->assertJsonPath('has_questionnaire', true);

        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'UPDATE_EVENT_OPTIONS']);
    }

    public function test_editing_other_details_does_not_create_an_options_audit_entry(): void
    {
        $admin = $this->makeUser('admin');
        $event = Event::create(['name' => 'E', 'status' => 'active']);

        $this->asUser($admin)->putJson("/api/events/{$event->id}", ['name' => 'Renamed'])->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'UPDATE_EVENT']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'UPDATE_EVENT_OPTIONS']);
    }

    public function test_assigned_users_are_only_shown_to_people_who_manage_events(): void
    {
        $enum  = $this->makeUser('survey_enumerator');
        $event = Event::create(['name' => 'E', 'status' => 'active']);
        $event->users()->attach($enum->id);

        $this->asUser($this->makeUser('child_officer'))->getJson("/api/events/{$event->id}")
            ->assertOk()->assertJsonMissingPath('user_ids')->assertJsonMissingPath('users');

        $this->asUser($this->makeUser('admin'))->getJson("/api/events/{$event->id}")
            ->assertOk()->assertJsonPath('user_ids.0', $enum->id);
    }

    public function test_sending_a_new_assignee_list_replaces_the_old_one(): void
    {
        $admin = $this->makeUser('admin');
        $a     = $this->makeUser('survey_enumerator');
        $b     = $this->makeUser('field_officer');
        $event = Event::create(['name' => 'E', 'status' => 'active']);
        $event->users()->attach($a->id);

        $this->asUser($admin)->putJson("/api/events/{$event->id}", ['user_ids' => [$b->id]])->assertOk();
        $this->assertSame([$b->id], $event->users()->pluck('users.id')->all());

        $this->asUser($admin)->putJson("/api/events/{$event->id}", ['user_ids' => []])->assertOk();
        $this->assertSame(0, $event->users()->count());
    }

    public function test_database_rejects_assigning_the_same_user_twice(): void
    {
        $enum  = $this->makeUser('survey_enumerator');
        $event = Event::create(['name' => 'E', 'status' => 'active']);

        DB::table('event_user')->insert(['event_id' => $event->id, 'user_id' => $enum->id]);

        $this->expectException(QueryException::class);
        DB::table('event_user')->insert(['event_id' => $event->id, 'user_id' => $enum->id]);
    }

        public function test_attendance_stays_closed_until_the_admin_enables_it(): void
    {
        $admin = $this->makeUser('admin');
        $enum  = $this->makeUser('survey_enumerator', ['Education']);
        $child = $this->makeChild('Education');
        $token = $this->qrTokenFor($child);

        $id = $this->asUser($admin)->postJson('/api/events', ['name' => 'Camp', 'user_ids' => [$enum->id]])
            ->assertCreated()->json('id');

        $this->asUser($enum)->postJson("/api/events/{$id}/attendance/scan", ['token' => $token])->assertStatus(422);

        $this->asUser($admin)->putJson("/api/events/{$id}", ['qr_attendance_enabled' => true])->assertOk();

        $this->asUser($enum)->postJson("/api/events/{$id}/attendance/scan", ['token' => $token])
            ->assertOk()->assertJsonPath('already_marked', false);
    }
}