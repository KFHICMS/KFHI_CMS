<?php

namespace Tests\Feature;

use App\Models\Event;
use Tests\KfhiTestCase;

class RolePermissionTest extends KfhiTestCase
{
    private const NON_ADMIN_ROLES = ['child_officer', 'field_officer', 'survey_enumerator'];

    public function test_only_admin_can_manage_users_and_view_audit_logs(): void
    {
        foreach (self::NON_ADMIN_ROLES as $role) {
            $user = $this->makeUser($role);
            $this->asUser($user)->getJson('/api/users')->assertForbidden();
            $this->asUser($user)->getJson('/api/audit-logs')->assertForbidden();
        }

        $admin = $this->makeUser('admin');
        $this->asUser($admin)->getJson('/api/users')->assertOk();
        $this->asUser($admin)->getJson('/api/audit-logs')->assertOk();
    }

    public function test_admin_can_create_a_user_with_a_role_and_password_is_hashed(): void
    {
        $admin = $this->makeUser('admin');

        $this->asUser($admin)->postJson('/api/users', [
            'name' => 'New Enumerator', 'email' => 'new.enum@kfhi.test',
            'password' => 'Strong@12345', 'role' => 'survey_enumerator',
        ])->assertCreated();

        $stored = \App\Models\User::where('email', 'new.enum@kfhi.test')->firstOrFail();
        $this->assertNotSame('Strong@12345', $stored->password);   // never plain text
        $this->assertTrue($stored->hasRole('survey_enumerator'));
    }

    public function test_weak_passwords_are_rejected_when_creating_a_user(): void
    {
        $admin = $this->makeUser('admin');

        $this->asUser($admin)->postJson('/api/users', [
            'name' => 'Weak', 'email' => 'weak@kfhi.test', 'password' => 'password1', 'role' => 'field_officer',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_only_admin_can_create_programs(): void
    {
        $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/programs', ['name' => 'New Program'])->assertForbidden();

        $this->asUser($this->makeUser('admin'))
            ->postJson('/api/programs', ['name' => 'New Program'])->assertCreated();
    }

    public function test_only_admin_can_create_events_with_attendance_and_questionnaire_enabled(): void
    {
        $payload = ['name' => 'Health Day', 'qr_attendance_enabled' => true, 'has_questionnaire' => true];

        foreach (self::NON_ADMIN_ROLES as $role) {
            $this->asUser($this->makeUser($role))->postJson('/api/events', $payload)->assertForbidden();
        }

        $this->asUser($this->makeUser('admin'))->postJson('/api/events', $payload)
            ->assertCreated()
            ->assertJsonPath('has_questionnaire', true)
            ->assertJsonPath('qr_attendance_enabled', true);
    }

    public function test_non_admin_roles_cannot_change_event_options(): void
    {
        $event = Event::create([
            'name' => 'Existing', 'status' => 'active',
            'qr_attendance_enabled' => false, 'has_questionnaire' => false,
        ]);

        foreach (self::NON_ADMIN_ROLES as $role) {
            $this->asUser($this->makeUser($role))
                ->putJson("/api/events/{$event->id}", ['qr_attendance_enabled' => true, 'has_questionnaire' => true])
                ->assertForbidden();
        }

        $event->refresh();
        $this->assertFalse($event->qr_attendance_enabled);
        $this->assertFalse($event->has_questionnaire);
    }

    public function test_field_officer_and_enumerator_cannot_create_or_archive_children(): void
    {
        $child = $this->makeChild('Education');

        foreach (['field_officer', 'survey_enumerator'] as $role) {
            $user = $this->makeUser($role, ['Education']);
            $this->asUser($user)->postJson('/api/children', ['full_name' => 'Blocked'])->assertForbidden();
            $this->asUser($user)->patchJson("/api/children/{$child->id}/archive")->assertForbidden();
        }

        $this->assertSame('active', $child->fresh()->status);
    }
}