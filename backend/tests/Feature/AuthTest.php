<?php

namespace Tests\Feature;

use Tests\KfhiTestCase;

class AuthTest extends KfhiTestCase
{
    public function test_valid_login_returns_token_user_and_permissions(): void
    {
        $user = $this->makeUser('child_officer', [], ['email' => 'officer@kfhi.test']);

        $this->postJson('/api/login', ['email' => 'officer@kfhi.test', 'password' => 'Test@12345'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles', 'permissions']])
            ->assertJsonPath('user.roles.0', 'child_officer');

        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'LOGIN']);
    }

    public function test_wrong_password_is_rejected_and_logged(): void
    {
        $user = $this->makeUser('field_officer', [], ['email' => 'field@kfhi.test']);

        $this->postJson('/api/login', ['email' => 'field@kfhi.test', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'LOGIN_FAILED']);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = $this->makeUser('survey_enumerator', [], ['email' => 'enum@kfhi.test', 'is_active' => false]);

        $this->postJson('/api/login', ['email' => 'enum@kfhi.test', 'password' => 'Test@12345'])
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'LOGIN_BLOCKED_INACTIVE']);
    }

    public function test_protected_endpoints_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/children')->assertUnauthorized();
        $this->getJson('/api/events')->assertUnauthorized();
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_logout_invalidates_the_token(): void
    {
        $user  = $this->makeUser('child_officer');
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_admin_deactivating_a_user_revokes_their_existing_token(): void
    {
        $admin = $this->makeUser('admin');
        $enum  = $this->makeUser('survey_enumerator');
        $token = $enum->createToken('t')->plainTextToken;

        $this->asUser($admin)->patchJson("/api/users/{$enum->id}/deactivate")->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    /**
     * EXPECTED TO FAIL TODAY (documents a gap against the spec):
     * a user deactivated directly in the database keeps a working token,
     * because nothing checks is_active on each request. Task 2 adds that check.
     */
    public function test_deactivated_user_with_an_existing_token_is_rejected(): void
    {
        $user  = $this->makeUser('survey_enumerator');
        $token = $user->createToken('t')->plainTextToken;
        $user->update(['is_active' => false]);

        $this->app['auth']->forgetGuards();
        $status = $this->withToken($token)->getJson('/api/me')->getStatusCode();

        $this->assertContains($status, [401, 403]);
    }
}