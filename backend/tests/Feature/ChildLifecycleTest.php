<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\KfhiTestCase;

class ChildLifecycleTest extends KfhiTestCase
{
    private const READ_ONLY = 'Archived records are read-only. Restore the child first.';

    public function test_archived_child_cannot_be_edited_through_the_api(): void
    {
        $child = $this->makeChild('Education', ['status' => 'archived', 'full_name' => 'Original Name']);

        $this->asUser($this->makeUser('child_officer'))
            ->putJson("/api/children/{$child->id}", ['full_name' => 'Changed'])
            ->assertStatus(422)
            ->assertJsonPath('message', self::READ_ONLY);

        $this->assertSame('Original Name', $child->fresh()->full_name);
    }

    public function test_archived_child_cannot_get_a_new_photo(): void
    {
        Storage::fake('local');
        $child = $this->makeChild('Education', ['status' => 'archived']);

        $this->asUser($this->makeUser('child_officer'))
            ->post("/api/children/{$child->id}/photo", ['photo' => UploadedFile::fake()->image('p.jpg', 300, 400)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', self::READ_ONLY);

        $this->assertSame([], Storage::disk('local')->allFiles('child-photos'));
    }

    public function test_an_archived_record_can_still_be_viewed(): void
    {
        $child = $this->makeChild('Education', ['status' => 'archived']);

        $this->asUser($this->makeUser('child_officer'))
            ->getJson("/api/children/{$child->id}")->assertOk()->assertJsonPath('status', 'archived');
    }

    public function test_child_can_be_restored_and_edited_again(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->patchJson("/api/children/{$child->id}/archive")->assertOk();
        $this->asUser($officer)->getJson('/api/children')->assertJsonPath('total', 0);

        $this->asUser($officer)->patchJson("/api/children/{$child->id}/restore")->assertOk();

        $this->assertSame('active', $child->fresh()->status);
        $this->asUser($officer)->getJson('/api/children')->assertJsonPath('total', 1);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $officer->id, 'action' => 'RESTORE_CHILD', 'entity_id' => $child->id]);

        $this->asUser($officer)->putJson("/api/children/{$child->id}", ['full_name' => 'Edited After Restore'])->assertOk();
    }

    public function test_restoring_does_not_bring_back_the_old_qr_code(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');
        $token   = $this->qrTokenFor($child);

        $this->asUser($officer)->patchJson("/api/children/{$child->id}/archive")->assertOk();
        $this->asUser($officer)->patchJson("/api/children/{$child->id}/restore")->assertOk();

        $this->asUser($officer)->postJson('/api/qr/resolve', ['token' => $token])->assertNotFound();
        $this->asUser($officer)->getJson("/api/children/{$child->id}/id-card")->assertOk()->assertJsonPath('qr', null);
    }

    public function test_an_active_child_cannot_be_restored(): void
    {
        $child = $this->makeChild('Education');

        $this->asUser($this->makeUser('child_officer'))
            ->patchJson("/api/children/{$child->id}/restore")->assertStatus(422);
    }

    public function test_only_roles_that_can_archive_can_restore(): void
    {
        $child = $this->makeChild('Education', ['status' => 'archived']);

        $this->asUser($this->makeUser('field_officer', ['Education']))
            ->patchJson("/api/children/{$child->id}/restore")->assertForbidden();

        $this->assertSame('archived', $child->fresh()->status);
    }

    public function test_opening_a_child_record_is_written_to_the_audit_log(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->getJson("/api/children/{$child->id}")->assertOk();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $officer->id, 'action' => 'VIEW_CHILD', 'entity_id' => $child->id]);
    }

    public function test_area_and_office_filters_treat_wildcard_characters_as_plain_text(): void
    {
        $this->makeChild('Education', ['area' => 'Galle',  'office_code' => 'LKA005']);
        $this->makeChild('Education', ['area' => 'Matara', 'office_code' => 'LKA007']);
        $officer = $this->makeUser('child_officer');

        // partial searches still work
        $this->asUser($officer)->getJson('/api/children?area=Gall')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?office_code=LKA0')->assertJsonPath('total', 2);

        // but % and _ no longer match everything
        $this->asUser($officer)->getJson('/api/children?area=' . urlencode('%'))->assertJsonPath('total', 0);
        $this->asUser($officer)->getJson('/api/children?area=' . urlencode('_alle'))->assertJsonPath('total', 0);
        $this->asUser($officer)->getJson('/api/children?office_code=' . urlencode('LKA%'))->assertJsonPath('total', 0);
    }
}