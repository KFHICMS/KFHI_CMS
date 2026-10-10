<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_renders_inside_the_dashboard_shell(): void
    {
        $response = $this->get('/dashboard/users');

        $response->assertOk()
            ->assertSee(route('dashboard.users'))
            ->assertSee('User Management')
            ->assertSee(asset('admin-ui/users.html').'?embedded=1')
            ->assertSee('<iframe', false);
    }
}
