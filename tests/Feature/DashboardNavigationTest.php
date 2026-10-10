<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_the_standalone_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin-ui/login.html');
    }

    public function test_user_management_renders_inside_the_dashboard_shell(): void
    {
        $response = $this->get('/dashboard/users');

        $response->assertOk()
            ->assertSee(route('dashboard.users'))
            ->assertSee('User Management')
            ->assertSee('data-users-src="'.asset('admin-ui/users.html').'?embedded=1"', false)
            ->assertSee('id="dashboardAuthButton"', false)
            ->assertSee('id="dashboardAuthLabel"', false)
            ->assertSee('sessionStorage.getItem', false)
            ->assertDontSee('<iframe src="'.asset('admin-ui/users.html'), false)
            ->assertSee('<iframe', false);
    }
}