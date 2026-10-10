<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Child;
use App\Models\QrToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_child_officer_dashboard_renders_current_metrics_and_recent_children(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        $activeChild = Child::create([
            'child_code' => 'CH-001',
            'full_name' => 'Active Child',
            'status' => 'active',
        ]);
        $archivedChild = Child::create([
            'child_code' => 'CH-002',
            'full_name' => 'Archived Child',
            'status' => 'archived',
        ]);

        QrToken::create(['child_id' => $activeChild->id, 'token' => 'active-token', 'is_active' => true]);
        QrToken::create(['child_id' => $archivedChild->id, 'token' => 'inactive-token', 'is_active' => false]);
        AuditLog::create(['action' => 'SCAN_QR']);

        $previousMonthScan = new AuditLog(['action' => 'SCAN_QR']);
        $previousMonthScan->created_at = now()->subMonth();
        $previousMonthScan->save();

        $response = $this->withoutVite()->get('/');

        $response->assertOk()
            ->assertViewIs('child_officer.childdashboard')
            ->assertViewHas('stats', fn (array $stats): bool => $stats['active_children'] === 1
                && $stats['active_qr_codes'] === 1
                && $stats['qr_scans'] === 1)
            ->assertViewHas('recentChildren', fn ($children): bool => $children->count() === 2
                && $children->contains('id', $activeChild->id));
    }

    public function test_child_officer_dashboard_is_available_at_its_previous_url(): void
    {
        $response = $this->withoutVite()->get('/child-officer/dashboard');

        $response->assertOk()
            ->assertViewIs('child_officer.childdashboard')
            ->assertViewHas('stats')
            ->assertViewHas('recentChildren');
    }

    public function test_field_officer_dashboard_renders_today_metrics_and_recent_children(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        $firstChild = Child::create([
            'child_code' => 'CH-101',
            'full_name' => 'First Child',
            'program' => 'Education',
        ]);
        $secondChild = Child::create([
            'child_code' => 'CH-102',
            'full_name' => 'Second Child',
            'program' => 'Education',
        ]);
        Child::create([
            'child_code' => 'CH-103',
            'full_name' => 'Third Child',
            'program' => 'Health',
        ]);

        QrToken::create(['child_id' => $firstChild->id, 'token' => 'today-token', 'is_active' => true]);
        $previousDayToken = QrToken::create([
            'child_id' => $secondChild->id,
            'token' => 'previous-day-token',
            'is_active' => false,
        ]);
        $previousDayToken->created_at = now()->subDay();
        $previousDayToken->save();
        AuditLog::create(['action' => 'SCAN_QR']);

        $previousDayScan = new AuditLog(['action' => 'SCAN_QR']);
        $previousDayScan->created_at = now()->subDay();
        $previousDayScan->save();

        $response = $this->withoutVite()->get('/field-officer/dashboard');

        $response->assertOk()
            ->assertViewIs('field_officer.fieldofficerdashboard')
            ->assertViewHas('stats', fn (array $stats): bool => $stats['programs'] === 2
                && $stats['children_registered_today'] === 3
                && $stats['qr_scans_today'] === 1
                && $stats['qr_codes_generated_today'] === 1)
            ->assertViewHas('recentChildren', fn ($children): bool => $children->count() === 3);
    }

    public function test_admin_dashboard_remains_available_at_dashboard_route(): void
    {
        $response = $this->withoutVite()->get('/dashboard');

        $response->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('stats.total_children', 1245);
    }
}
