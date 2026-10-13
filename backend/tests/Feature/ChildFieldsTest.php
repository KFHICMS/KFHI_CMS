<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\KfhiTestCase;

class ChildFieldsTest extends KfhiTestCase
{
    private function fullPayload(array $override = []): array
    {
        return array_merge([
            'child_code'             => 'LKA005001',
            'full_name'              => 'Amara Perera',
            'name_korean'            => '아마라',
            'alias'                  => 'Ama',
            'date_of_birth'          => now()->subYears(10)->subMonth()->toDateString(),
            'gender'                 => 'Female',
            'religion'               => 'Buddhism',
            'area'                   => 'Galle',
            'gnd'                    => 'Hikkaduwa North',
            'office_code'            => 'LKA005',
            'office_name'            => 'Galle Office',
            'service_state'          => 'Active',
            'sponsor_state'          => 'Sponsored',
            'guardian_type'          => 'Parent',
            'caregiver'              => 'Nimali Perera',
            'curriculum'             => 'National',
            'grade'                  => '5',
            'favorite_subject'       => 'Art',
            'pass_fail'              => 'Pass',
            'dream'                  => 'Teacher',
            'dream_description'      => 'Wants to teach children in her village.',
            'favorite_activity'      => 'Drawing',
            'health'                 => 'Good',
            'medical_info'           => 'Seasonal allergies',
            'disability_type'        => 'Hearing',
            'disability_description' => 'Mild hearing loss in left ear.',
            'program'                => 'Education',
        ], $override);
    }

    public function test_child_can_be_registered_with_all_kfhi_fields(): void
    {
        $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/children', $this->fullPayload())
            ->assertCreated()
            ->assertJsonPath('child_code', 'LKA005001')
            ->assertJsonPath('name_korean', '아마라')
            ->assertJsonPath('office_name', 'Galle Office')
            ->assertJsonPath('pass_fail', 'Pass')
            ->assertJsonPath('disability_type', 'Hearing');

        $this->assertDatabaseHas('children', [
            'child_code' => 'LKA005001', 'area' => 'Galle', 'gnd' => 'Hikkaduwa North', 'office_code' => 'LKA005',
        ]);
    }

    public function test_age_is_calculated_from_the_birthday_and_never_stored(): void
    {
        $child = $this->makeChild('Education', ['date_of_birth' => now()->subYears(10)->subMonth()->toDateString()]);

        $this->asUser($this->makeUser('child_officer'))->getJson("/api/children/{$child->id}")
            ->assertOk()->assertJsonPath('age', 10);

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('children', 'age'));
    }

    public function test_entered_child_code_must_be_unique(): void
    {
        $this->makeChild('Education', ['child_code' => 'LKA005001']);

        $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/children', $this->fullPayload(['child_code' => 'LKA005001']))
            ->assertStatus(422)->assertJsonValidationErrors('child_code');
    }

    public function test_child_code_is_generated_when_not_entered(): void
    {
        $res = $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/children', ['full_name' => 'No Code Child'])->assertCreated();

        $this->assertNotEmpty($res->json('child_code'));
    }

    public function test_child_code_rejects_unsafe_characters(): void
    {
        $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/children', ['full_name' => 'X', 'child_code' => 'BAD CODE/../'])
            ->assertStatus(422)->assertJsonValidationErrors('child_code');
    }

    public function test_child_code_cannot_be_changed_by_an_update(): void
    {
        $child = $this->makeChild('Education', ['child_code' => 'LKA005001']);

        $this->asUser($this->makeUser('child_officer'))
            ->putJson("/api/children/{$child->id}", ['full_name' => 'Renamed', 'child_code' => 'HACKED'])
            ->assertOk();

        $child->refresh();
        $this->assertSame('LKA005001', $child->child_code);
        $this->assertSame('Renamed', $child->full_name);
    }

    public function test_pass_fail_only_accepts_pass_or_fail(): void
    {
        $this->asUser($this->makeUser('child_officer'))
            ->postJson('/api/children', ['full_name' => 'X', 'pass_fail' => 'Maybe'])
            ->assertStatus(422)->assertJsonValidationErrors('pass_fail');
    }

    public function test_health_and_disability_fields_are_hidden_from_field_roles_and_encrypted_at_rest(): void
    {
        $child = $this->makeChild('Education', [
            'health' => 'Chronic', 'disability_type' => 'Hearing', 'disability_description' => 'Left ear',
            'area' => 'Galle', 'name_korean' => '아마라',
        ]);

        $this->asUser($this->makeUser('field_officer', ['Education']))->getJson("/api/children/{$child->id}")
            ->assertOk()
            ->assertJsonPath('area', 'Galle')                 // non-sensitive: visible
            ->assertJsonPath('name_korean', '아마라')
            ->assertJsonMissingPath('health')                 // sensitive: hidden
            ->assertJsonMissingPath('medical_info')
            ->assertJsonMissingPath('disability_type')
            ->assertJsonMissingPath('disability_description');

        $this->asUser($this->makeUser('child_officer'))->getJson("/api/children/{$child->id}")
            ->assertOk()
            ->assertJsonPath('disability_type', 'Hearing')
            ->assertJsonPath('health', 'Chronic');

        $row = DB::table('children')->where('id', $child->id)->first();
        foreach (['health', 'medical_info', 'disability_type', 'disability_description'] as $col) {
            $this->assertStringNotContainsString('Hearing', (string) $row->$col);
            $this->assertStringNotContainsString('Chronic', (string) $row->$col);
        }
    }

    public function test_child_list_can_be_filtered_and_searched_by_the_new_fields(): void
    {
        $this->makeChild('Education', ['area' => 'Galle',  'office_code' => 'LKA005', 'gender' => 'Female',
                                       'service_state' => 'Active', 'sponsor_state' => 'Sponsored',
                                       'alias' => 'Ama', 'name_korean' => '아마라']);
        $this->makeChild('Education', ['area' => 'Matara', 'office_code' => 'LKA007', 'gender' => 'Male',
                                       'service_state' => 'Closed', 'sponsor_state' => 'Waiting']);

        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->getJson('/api/children?area=Galle')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?office_code=LKA007')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?gender=Female')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?service_state=Closed')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?sponsor_state=Sponsored')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?q=Ama')->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children?q=' . urlencode('아마라'))->assertJsonPath('total', 1);
        $this->asUser($officer)->getJson('/api/children')->assertJsonPath('total', 2);
    }

    public function test_filters_never_widen_a_field_officers_access(): void
    {
        $this->makeChild('Health', ['area' => 'Galle']);
        $officer = $this->makeUser('field_officer', ['Education']);

        $this->asUser($officer)->getJson('/api/children?area=Galle')->assertJsonPath('total', 0);
    }
}