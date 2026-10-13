<?php

namespace Tests\Feature;

use App\Models\Event;
use Tests\KfhiTestCase;

class DashboardTest extends KfhiTestCase
{
    public function test_dashboard_counts_are_real_and_exclude_archived_and_inactive(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeUser('field_officer')->forceFill(['is_active' => false])->save();

        $this->makeChild('Education');
        $this->makeChild('Education')->forceFill(['status' => 'archived'])->save();

        Event::create(['name' => 'Past', 'status' => 'active', 'start_at' => now()->subDays(3), 'end_at' => now()->subDays(2)]);
        Event::create(['name' => 'Now',  'status' => 'active', 'start_at' => now()->subHour(),  'end_at' => now()->addHour()]);
        Event::create(['name' => 'Soon', 'status' => 'active', 'start_at' => now()->addDays(2), 'end_at' => now()->addDays(3)]);
        Event::create(['name' => 'Shut', 'status' => 'closed', 'start_at' => now()->addDays(2)]);

        $this->asUser($admin)->getJson('/api/reports/dashboard')->assertOk()
            ->assertJsonPath('cards.children', 1)
            ->assertJsonPath('cards.users', 2)
            ->assertJsonPath('cards.events', 4)
            ->assertJsonPath('activities.to_do', 1)
            ->assertJsonPath('activities.in_progress', 1)
            ->assertJsonPath('activities.completed', 2);
    }

    public function test_roles_without_report_permission_cannot_open_the_dashboard_data(): void
    {
        foreach (['field_officer', 'survey_enumerator'] as $role) {
            $this->asUser($this->makeUser($role))->getJson('/api/reports/dashboard')->assertForbidden();
        }
    }
}