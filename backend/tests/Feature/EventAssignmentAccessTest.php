<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use Tests\KfhiTestCase;

class EventAssignmentAccessTest extends KfhiTestCase
{
    private function openEvent(string $name = 'Health Day'): Event
    {
        return Event::create([
            'name' => $name, 'status' => 'active',
            'qr_attendance_enabled' => true, 'has_questionnaire' => false,
        ]);
    }

    public function test_unassigned_enumerator_cannot_record_attendance(): void
    {
        $event = $this->openEvent();
        $token = $this->qrTokenFor($this->makeChild('Education'));
        $enum  = $this->makeUser('survey_enumerator', ['Education']);   // right program, NOT assigned to the event

        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_assigned_enumerator_can_record_attendance(): void
    {
        $event = $this->openEvent();
        $token = $this->qrTokenFor($this->makeChild('Education'));
        $enum  = $this->makeUser('survey_enumerator', ['Education']);
        $event->users()->attach($enum->id);

        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertOk();

        $this->assertSame(1, Attendance::count());
    }

    public function test_assigned_enumerator_is_still_limited_to_their_programs(): void
    {
        $event = $this->openEvent();
        $token = $this->qrTokenFor($this->makeChild('Health'));
        $enum  = $this->makeUser('survey_enumerator', ['Education']);
        $event->users()->attach($enum->id);

        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    public function test_unassigned_enumerator_cannot_view_the_attendance_list(): void
    {
        $event = $this->openEvent();
        $enum  = $this->makeUser('survey_enumerator');

        $this->asUser($enum)->getJson("/api/events/{$event->id}/attendance")->assertForbidden();

        $event->users()->attach($enum->id);

        $this->asUser($enum)->getJson("/api/events/{$event->id}/attendance")->assertOk();
    }

    public function test_enumerator_only_sees_and_opens_assigned_events(): void
    {
        $mine   = $this->openEvent('My event');
        $other  = $this->openEvent('Other event');
        $enum   = $this->makeUser('survey_enumerator');
        $mine->users()->attach($enum->id);

        $this->asUser($enum)->getJson('/api/events')
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'My event');

        $this->asUser($enum)->getJson("/api/events/{$mine->id}")->assertOk();
        $this->asUser($enum)->getJson("/api/events/{$other->id}")->assertForbidden();
    }

    public function test_admin_child_officer_and_field_officer_see_every_event(): void
    {
        $this->openEvent('One');
        $this->openEvent('Two');

        foreach (['admin', 'child_officer', 'field_officer'] as $role) {
            $this->asUser($this->makeUser($role))->getJson('/api/events')->assertOk()->assertJsonCount(2);
        }
    }

    public function test_field_officer_is_not_restricted_by_event_assignment(): void
    {
        $event   = $this->openEvent();
        $token   = $this->qrTokenFor($this->makeChild('Education'));
        $officer = $this->makeUser('field_officer', ['Education']);   // not assigned to the event

        $this->asUser($officer)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertOk();
    }

    public function test_removing_an_assignment_removes_access_immediately(): void
    {
        $admin = $this->makeUser('admin');
        $enum  = $this->makeUser('survey_enumerator', ['Education']);
        $event = $this->openEvent();
        $event->users()->attach($enum->id);
        $token = $this->qrTokenFor($this->makeChild('Education'));

        $this->asUser($enum)->getJson("/api/events/{$event->id}")->assertOk();

        $this->asUser($admin)->putJson("/api/events/{$event->id}", ['user_ids' => []])->assertOk();

        $this->asUser($enum)->getJson("/api/events/{$event->id}")->assertForbidden();
        $this->asUser($enum)->postJson("/api/events/{$event->id}/attendance/scan", ['token' => $token])
            ->assertForbidden();
    }
}