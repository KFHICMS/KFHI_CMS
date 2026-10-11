<?php

namespace Tests\Feature;

use Tests\KfhiTestCase;

class ChildAccessTest extends KfhiTestCase
{
    public function test_field_officer_only_sees_children_in_assigned_programs(): void
    {
        $this->makeChild('Education');
        $this->makeChild('Health');
        $officer = $this->makeUser('field_officer', ['Education']);

        $this->asUser($officer)->getJson('/api/children')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.program', 'Education');
    }

    public function test_field_officer_with_no_assigned_program_sees_no_children(): void
    {
        $this->makeChild('Education');

        $this->asUser($this->makeUser('field_officer'))->getJson('/api/children')
            ->assertOk()->assertJsonPath('total', 0);
    }

    public function test_child_officer_and_admin_see_all_children(): void
    {
        $this->makeChild('Education');
        $this->makeChild('Health');

        $this->asUser($this->makeUser('child_officer'))->getJson('/api/children')->assertJsonPath('total', 2);
        $this->asUser($this->makeUser('admin'))->getJson('/api/children')->assertJsonPath('total', 2);
    }

    public function test_child_outside_the_officers_programs_cannot_be_opened_by_url(): void
    {
        $health = $this->makeChild('Health');
        $officer = $this->makeUser('field_officer', ['Education']);

        $this->asUser($officer)->getJson("/api/children/{$health->id}")->assertForbidden();
    }

    public function test_health_and_emergency_data_is_hidden_from_field_roles(): void
    {
        $child = $this->makeChild('Education');

        foreach (['field_officer', 'survey_enumerator'] as $role) {
            $user = $this->makeUser($role, ['Education']);
            $this->asUser($user)->getJson("/api/children/{$child->id}")
                ->assertOk()
                ->assertJsonPath('child_code', $child->child_code)
                ->assertJsonMissingPath('medical_info')
                ->assertJsonMissingPath('special_requirements')
                ->assertJsonMissingPath('emergency_contacts');
        }
    }

    public function test_child_officer_can_see_health_and_emergency_data(): void
    {
        $child = $this->makeChild('Education');

        $this->asUser($this->makeUser('child_officer'))->getJson("/api/children/{$child->id}")
            ->assertOk()
            ->assertJsonPath('medical_info', 'Asthma')
            ->assertJsonPath('emergency_contacts', '0771234567');
    }

    public function test_health_data_is_encrypted_in_the_database(): void
    {
        $child = $this->makeChild('Education');

        $raw = \Illuminate\Support\Facades\DB::table('children')->where('id', $child->id)->value('medical_info');
        $this->assertNotSame('Asthma', $raw);
        $this->assertStringNotContainsString('Asthma', $raw);
    }

    public function test_child_officer_can_register_a_child_and_validation_applies(): void
    {
        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->postJson('/api/children', ['full_name' => 'Amara Perera', 'program' => 'Education'])
            ->assertCreated();
        $this->assertDatabaseHas('children', ['full_name' => 'Amara Perera']);

        $this->asUser($officer)->postJson('/api/children', ['program' => 'Education'])
            ->assertStatus(422)->assertJsonValidationErrors('full_name');
    }

    public function test_archived_children_are_hidden_from_the_default_list(): void
    {
        $child = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->patchJson("/api/children/{$child->id}/archive")->assertOk();

        $this->assertSame('archived', $child->fresh()->status);
        $this->asUser($officer)->getJson('/api/children')->assertJsonPath('total', 0);
    }
}