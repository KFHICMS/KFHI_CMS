<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use Tests\KfhiTestCase;

class MonthlyOverviewTest extends KfhiTestCase
{
    public function test_monthly_numbers_come_from_the_database_for_the_chosen_month(): void
    {
        $admin = $this->makeUser('admin');

        // two children registered in May 2026, one in June
        $may1 = $this->makeChild('Education');
        $may2 = $this->makeChild('Education');
        $jun  = $this->makeChild('Education');
        $may1->forceFill(['created_at' => '2026-05-10 09:00:00'])->save();
        $may2->forceFill(['created_at' => '2026-05-20 09:00:00'])->save();
        $jun->forceFill(['created_at' => '2026-06-02 09:00:00'])->save();

        // events: finished (active), finished (closed), cancelled, and one in June
        $done   = Event::create(['name' => 'Done',   'status' => 'active',    'start_at' => '2026-05-03 10:00:00', 'end_at' => '2026-05-03 12:00:00']);
        Event::create(['name' => 'Closed', 'status' => 'closed',    'start_at' => '2026-05-05 10:00:00']);
        Event::create(['name' => 'Called', 'status' => 'cancelled', 'start_at' => '2026-05-06 10:00:00']);
        Event::create(['name' => 'June',   'status' => 'active',    'start_at' => '2026-06-03 10:00:00', 'end_at' => '2026-06-03 12:00:00']);

        $a = Attendance::create(['event_id' => $done->id, 'child_id' => $may1->id, 'status' => 'present', 'marked_by' => $admin->id]);
        $b = Attendance::create(['event_id' => $done->id, 'child_id' => $jun->id,  'status' => 'present', 'marked_by' => $admin->id]);
        $a->forceFill(['created_at' => '2026-05-03 10:30:00'])->save();
        $b->forceFill(['created_at' => '2026-06-03 10:30:00'])->save();

        $this->asUser($admin)->getJson('/api/reports/monthly?month=2026-05')->assertOk()
            ->assertJsonPath('label', 'May 2026')
            ->assertJsonPath('children_registered', 2)
            ->assertJsonPath('events_scheduled', 2)          // cancelled one is excluded
            ->assertJsonPath('events_held', 2)
            ->assertJsonPath('attendance_records', 1)
            ->assertJsonPath('activities.completed', 2)
            ->assertJsonPath('activities.outstanding', 0)
            ->assertJsonPath('completion_rate', 100)
            ->assertJsonPath('questionnaire_submissions', null);

        $this->asUser($admin)->getJson('/api/reports/monthly?month=2026-06')->assertOk()
            ->assertJsonPath('children_registered', 1)
            ->assertJsonPath('events_scheduled', 1)
            ->assertJsonPath('attendance_records', 1);
    }

    public function test_upcoming_events_are_outstanding_and_an_empty_month_has_no_rate(): void
    {
        $admin = $this->makeUser('admin');
        $soon  = now()->addMonth()->startOfMonth()->addDays(3);

        Event::create(['name' => 'Soon', 'status' => 'active', 'start_at' => $soon, 'end_at' => $soon->copy()->addHours(2)]);

        $this->asUser($admin)->getJson('/api/reports/monthly?month=' . $soon->format('Y-m'))->assertOk()
            ->assertJsonPath('events_scheduled', 1)
            ->assertJsonPath('events_held', 0)
            ->assertJsonPath('activities.outstanding', 1)
            ->assertJsonPath('completion_rate', 0);

        $this->asUser($admin)->getJson('/api/reports/monthly?month=2020-01')->assertOk()
            ->assertJsonPath('events_scheduled', 0)
            ->assertJsonPath('completion_rate', null);
    }

    public function test_default_month_is_the_current_one_and_bad_input_is_rejected(): void
    {
        $admin = $this->makeUser('admin');

        $this->asUser($admin)->getJson('/api/reports/monthly')->assertOk()
            ->assertJsonPath('month', now()->format('Y-m'));

        $this->asUser($admin)->getJson('/api/reports/monthly?month=banana')->assertStatus(422);
        $this->asUser($admin)->getJson('/api/reports/monthly?month=2026-13')->assertStatus(422);
    }

    public function test_roles_without_report_permission_are_refused(): void
    {
        foreach (['field_officer', 'survey_enumerator'] as $role) {
            $this->asUser($this->makeUser($role))->getJson('/api/reports/monthly')->assertForbidden();
        }
    }
}