<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::where('email', 'admin@kfhi.test')->firstOrFail();
    }

    // ---------- Helpers ----------

    private function actingAsApi(User $user): static
    {
        app('auth')->forgetGuards();
        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    // A user with a role. The factory password is "password".
    private function makeUser(string $role, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole($role);

        return $user;
    }

    // ---------- Deactivate / activate ----------

    public function test_deactivating_a_user_logs_him_out_and_writes_an_audit_log(): void
    {
        $target = $this->makeUser('field_officer');
        $target->createToken('old-session');

        $this->actingAsApi($this->admin)
            ->patchJson("/api/admin/users/{$target->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse((bool) $target->fresh()->is_active);
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action'    => 'USER_DEACTIVATED',
            'entity_id' => $target->id,
            'user_id'   => $this->admin->id,
        ]);
    }

    public function test_old_token_stops_working_after_deactivation(): void
    {
        $target = $this->makeUser('field_officer');
        $plain  = $target->createToken('old-session')->plainTextToken;

        $this->actingAsApi($this->admin)
            ->patchJson("/api/admin/users/{$target->id}/deactivate")
            ->assertOk();

        app('auth')->forgetGuards();
        $this->withToken($plain)->getJson('/api/me')->assertStatus(401);
    }

    public function test_admin_cannot_deactivate_himself(): void
    {
        $this->actingAsApi($this->admin)
            ->patchJson("/api/admin/users/{$this->admin->id}/deactivate")
            ->assertStatus(422);

        $this->assertTrue((bool) $this->admin->fresh()->is_active);
    }

    public function test_the_last_active_admin_cannot_be_deactivated(): void
    {
        // An unusual case: a second admin who is already inactive but still has a token.
        $inactiveAdmin = $this->makeUser('admin', ['email' => 'admin2@kfhi.test']);
        $inactiveAdmin->forceFill(['is_active' => false])->save();

        $this->actingAsApi($inactiveAdmin)
            ->patchJson("/api/admin/users/{$this->admin->id}/deactivate")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot deactivate the last active admin.');

        $this->assertTrue((bool) $this->admin->fresh()->is_active);
    }

    public function test_an_admin_can_be_deactivated_when_another_active_admin_exists(): void
    {
        $secondAdmin = $this->makeUser('admin', ['email' => 'admin2@kfhi.test']);

        $this->actingAsApi($secondAdmin)
            ->patchJson("/api/admin/users/{$this->admin->id}/deactivate")
            ->assertOk();

        $this->assertFalse((bool) $this->admin->fresh()->is_active);
    }

    public function test_deactivating_twice_writes_only_one_audit_log(): void
    {
        $target = $this->makeUser('field_officer');

        $this->actingAsApi($this->admin)->patchJson("/api/admin/users/{$target->id}/deactivate")->assertOk();
        $this->actingAsApi($this->admin)->patchJson("/api/admin/users/{$target->id}/deactivate")->assertOk();

        $this->assertSame(1, AuditLog::where('action', 'USER_DEACTIVATED')->count());
    }

    public function test_admin_can_activate_a_user_again(): void
    {
        $target = $this->makeUser('field_officer');
        $target->forceFill(['is_active' => false])->save();

        $this->actingAsApi($this->admin)
            ->patchJson("/api/admin/users/{$target->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue((bool) $target->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_ACTIVATED', 'entity_id' => $target->id]);
    }

    // ---------- Reset password ----------

    public function test_admin_can_reset_a_password(): void
    {
        $target = $this->makeUser('field_officer');
        $target->createToken('old-session');

        $response = $this->actingAsApi($this->admin)
            ->postJson("/api/admin/users/{$target->id}/reset-password")
            ->assertOk()
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonStructure(['temporary_password']);

        $temp = $response->json('temporary_password');

        // Browsers must not store the response.
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $target = $target->fresh();
        $this->assertTrue(Hash::check($temp, $target->password));
        $this->assertFalse(Hash::check('password', $target->password)); // old password is dead
        $this->assertTrue((bool) $target->must_change_password);
        $this->assertSame(0, $target->tokens()->count());               // old sessions are dead

        $log = AuditLog::where('action', 'USER_PASSWORD_RESET')->firstOrFail();
        $this->assertSame($target->id, $log->entity_id);
        $this->assertStringNotContainsString($temp, $log->details);
    }

    public function test_admin_cannot_reset_his_own_password_here(): void
    {
        $this->actingAsApi($this->admin)
            ->postJson("/api/admin/users/{$this->admin->id}/reset-password")
            ->assertStatus(422);
    }

    // ---------- Login ----------

    public function test_deactivated_user_cannot_log_in(): void
    {
        $target = $this->makeUser('field_officer', ['email' => 'off@kfhi.test']);
        $target->forceFill(['is_active' => false])->save();

        $this->postJson('/api/login', ['email' => 'off@kfhi.test', 'password' => 'password'])
            ->assertStatus(403);

        $this->assertDatabaseHas('audit_logs', ['action' => 'LOGIN_BLOCKED', 'user_id' => $target->id]);
        $this->assertSame(0, $target->tokens()->count()); // no token was created
    }

    public function test_wrong_password_does_not_reveal_that_an_account_is_disabled(): void
    {
        $target = $this->makeUser('field_officer', ['email' => 'off@kfhi.test']);
        $target->forceFill(['is_active' => false])->save();

        // Same answer as for any wrong password.
        $this->postJson('/api/login', ['email' => 'off@kfhi.test', 'password' => 'WrongPass1!'])
            ->assertStatus(422);
    }

    public function test_login_saves_last_login_and_reports_must_change_password(): void
    {
        $target = $this->makeUser('field_officer', ['email' => 'off@kfhi.test']);
        $target->forceFill(['must_change_password' => true])->save();

        $this->postJson('/api/login', ['email' => 'off@kfhi.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.must_change_password', true)
            ->assertJsonStructure(['token']);

        $this->assertNotNull($target->fresh()->last_login_at);
    }

    // ---------- Forced password change ----------

    public function test_user_with_temporary_password_is_blocked_everywhere_except_me_and_logout(): void
    {
        $target = $this->makeUser('field_officer');
        $target->forceFill(['must_change_password' => true])->save();

        $this->actingAsApi($target)
            ->getJson('/api/children')
            ->assertStatus(403)
            ->assertJsonPath('code', 'password_change_required');

        $this->getJson('/api/me')->assertOk();
    }

    public function test_change_password_rejects_wrong_weak_and_same_passwords(): void
    {
        $user = $this->makeUser('field_officer');
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAsApi($user);

        // Wrong current password
        $this->postJson('/api/change-password', [
            'current_password'      => 'WrongOne1!',
            'password'              => 'NewPass#2026Kfhi',
            'password_confirmation' => 'NewPass#2026Kfhi',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        // Weak new password
        $this->postJson('/api/change-password', [
            'current_password'      => 'password',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        // Confirmation does not match
        $this->postJson('/api/change-password', [
            'current_password'      => 'password',
            'password'              => 'NewPass#2026Kfhi',
            'password_confirmation' => 'Different#2026Kfhi',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->assertTrue((bool) $user->fresh()->must_change_password); // nothing changed
    }

    public function test_user_can_change_password_and_gets_full_access_back(): void
    {
        $user = $this->makeUser('field_officer');
        $user->forceFill(['must_change_password' => true])->save();
        $user->createToken('other-device');

        $this->actingAsApi($user)->postJson('/api/change-password', [
            'current_password'      => 'password',
            'password'              => 'NewPass#2026Kfhi',
            'password_confirmation' => 'NewPass#2026Kfhi',
        ])->assertOk();

        $user = $user->fresh();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertTrue(Hash::check('NewPass#2026Kfhi', $user->password));
        $this->assertSame(1, $user->tokens()->count()); // only the current session is kept
        $this->assertDatabaseHas('audit_logs', ['action' => 'PASSWORD_CHANGED', 'user_id' => $user->id]);

        // The same token now passes the forced-change guard (no more 403).
        $status = $this->getJson('/api/children')->getStatusCode();
        $this->assertNotSame(403, $status);
    }
}