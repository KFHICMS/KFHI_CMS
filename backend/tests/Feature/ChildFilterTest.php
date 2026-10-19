<?php

namespace Tests\Feature;

use Tests\KfhiTestCase;

class ChildFilterTest extends KfhiTestCase
{
    public function test_list_filters_work_and_never_widen_a_field_officers_access(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeChild('Education', ['gender' => 'Female', 'area' => 'Galle', 'office_code' => 'LKA005', 'service_state' => 'Active', 'sponsor_state' => 'Sponsored']);
        $this->makeChild('Education', ['gender' => 'Male',   'area' => 'Kandy', 'office_code' => 'LKA009', 'service_state' => 'Inactive', 'sponsor_state' => 'Waiting']);
        $this->makeChild('Other',     ['gender' => 'Female', 'area' => 'Galle', 'office_code' => 'LKA005', 'service_state' => 'Active', 'sponsor_state' => 'Sponsored']);

        $count = fn ($user, $q) => count($this->asUser($user)->getJson('/api/children?' . $q)->assertOk()->json('data'));

        $this->assertSame(2, $count($admin, 'gender=Female'));
        $this->assertSame(2, $count($admin, 'area=gal'));
        $this->assertSame(1, $count($admin, 'office_code=009'));
        $this->assertSame(2, $count($admin, 'service_state=Active'));
        $this->assertSame(1, $count($admin, 'sponsor_state=Waiting'));
        $this->assertSame(1, $count($admin, 'gender=Female&program=Other'));

        $field = $this->makeUser('field_officer', ['Education']);
        $this->assertSame(1, $count($field, 'gender=Female'));   // the Female child in "Other" stays hidden
        $this->assertSame(2, $count($field, ''));
    }
}