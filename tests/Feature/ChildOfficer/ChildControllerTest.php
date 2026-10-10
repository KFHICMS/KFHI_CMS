<?php

namespace Tests\Feature\ChildOfficer;

use App\Models\Child;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ChildControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_json_child_list_returns_filtered_child_details_without_private_fields(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        Child::create([
            'child_code' => 'CH-201',
            'full_name' => 'First Child',
            'name_english' => 'First Child',
            'name_korean' => '첫번째',
            'date_of_birth' => '2018-10-10',
            'gender' => 'Female',
            'area' => 'North',
            'office_name' => 'Office A',
            'grade' => '3',
            'medical_info' => 'Private medical note',
            'emergency_contacts' => 'Private contact',
        ]);
        Child::create([
            'child_code' => 'CH-201-2',
            'full_name' => 'Second Child',
            'name_english' => 'Second Child',
            'gender' => 'Male',
        ]);

        $response = $this->getJson('/child-officer/children?search=CH-201&gender=Female');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.child_code', 'CH-201')
            ->assertJsonPath('0.name_english', 'First Child')
            ->assertJsonPath('0.age', 8)
            ->assertJsonMissingPaths(['0.medical_info', '0.emergency_contacts']);
    }

    public function test_html_child_list_still_renders_the_paginated_view(): void
    {
        $response = $this->withoutVite()->get('/child-officer/children');

        $response->assertOk()
            ->assertViewIs('child_officer.children.index')
            ->assertViewHas('children');
    }
}
