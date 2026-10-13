<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\KfhiTestCase;

class UserProfileAndPasswordTest extends KfhiTestCase
{
    private const TEMP = 'Temp@12345';
    private const NEW  = 'Brand@67890';

    private function createViaApi(array $extra = []): array
    {
        $admin = $this->makeUser('admin');

        $res = $this->asUser($admin)->postJson('/api/users', array_merge([
            'name'     => 'New Enumerator',
            'email'    => 'enum.new@kfhi.test',
            'password' => self::TEMP,
            'role'     => 'survey_enumerator',
        ], $extra));

        return [$admin, $res];
    }

    private function loginToken(string $email, string $password): string
    {
        return $this->postJson('/api/login', ['email' => $email, 'password' => $password])
            ->assertOk()->json('token');
    }

    private function withFreshToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    public function test_user_gets_username_phone_and_defaults_when_created(): void
    {
        [, $res] = $this->createViaApi(['phone' => '+94 77 123 4567']);

        $res->assertCreated()
            ->assertJsonPath('username', 'enum.new')
            ->assertJsonPath('phone', '+94 77 123 4567')
            ->assertJsonPath('must_change_password', true)
            ->assertJsonPath('has_photo', false)
            ->assertJsonMissingPath('password');

        $user = User::where('email', 'enum.new@kfhi.test')->first();
        $this->assertTrue(Hash::check(self::TEMP, $user->password));
        $this->assertNotNull($res->json('date_joined'));
    }

    public function test_generated_usernames_never_collide(): void
    {
        $admin = $this->makeUser('admin');

        foreach (['sam@a.test', 'sam@b.test'] as $email) {
            $this->asUser($admin)->postJson('/api/users', [
                'name' => 'Sam', 'email' => $email, 'password' => self::TEMP, 'role' => 'field_officer',
            ])->assertCreated();
        }

        $this->assertSame(['sam', 'sam2'], User::whereIn('email', ['sam@a.test', 'sam@b.test'])
            ->orderBy('id')->pluck('username')->all());
    }

    public function test_username_must_be_unique_ignoring_case_and_well_formed(): void
    {
        $admin = $this->makeUser('admin');
        $this->createViaApi(['username' => 'Kamal.P']);

        $this->asUser($admin)->postJson('/api/users', [
            'name' => 'Other', 'email' => 'other@kfhi.test', 'password' => self::TEMP,
            'role' => 'field_officer', 'username' => 'KAMAL.p',
        ])->assertStatus(422)->assertJsonValidationErrors('username');

        $this->asUser($admin)->postJson('/api/users', [
            'name' => 'Bad', 'email' => 'bad@kfhi.test', 'password' => self::TEMP,
            'role' => 'field_officer', 'username' => 'has space!',
        ])->assertStatus(422)->assertJsonValidationErrors('username');

        $this->assertSame('kamal.p', User::where('email', 'enum.new@kfhi.test')->value('username'));
    }

    public function test_phone_is_validated_and_can_be_cleared(): void
    {
        $admin = $this->makeUser('admin');
        [, $res] = $this->createViaApi(['phone' => '0771234567']);
        $id = $res->json('id');

        $this->asUser($admin)->putJson("/api/users/$id", ['phone' => 'call me maybe'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');

        $this->asUser($admin)->putJson("/api/users/$id", ['phone' => null])
            ->assertOk()->assertJsonPath('phone', null);
    }

    public function test_new_account_is_locked_to_the_password_change_until_it_is_done(): void
    {
        $this->createViaApi();

        $token = $this->loginToken('enum.new@kfhi.test', self::TEMP);

        $this->withFreshToken($token)->getJson('/api/me')
            ->assertOk()->assertJsonPath('must_change_password', true);

        $this->withFreshToken($token)->getJson('/api/children')
            ->assertForbidden()->assertJsonPath('code', 'password_change_required');
        $this->withFreshToken($token)->getJson('/api/events')
            ->assertForbidden()->assertJsonPath('code', 'password_change_required');
    }

    public function test_changing_the_password_unlocks_the_account_and_is_audited(): void
    {
        [, $res] = $this->createViaApi();
        $userId = $res->json('id');

        $token = $this->loginToken('enum.new@kfhi.test', self::TEMP);
        $other = $this->loginToken('enum.new@kfhi.test', self::TEMP);

        $this->withFreshToken($token)->postJson('/api/change-password', [
            'current_password'      => self::TEMP,
            'password'              => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertOk();

        $user = User::find($userId);
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check(self::NEW, $user->password));

        $this->withFreshToken($token)->getJson('/api/children')->assertOk();
        $this->withFreshToken($other)->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/login', ['email' => 'enum.new@kfhi.test', 'password' => self::TEMP])
            ->assertStatus(422);

        $this->assertSame(1, AuditLog::where('action', 'PASSWORD_CHANGED')->where('user_id', $userId)->count());
        $this->assertSame(0, AuditLog::where('details', 'like', '%' . self::NEW . '%')->count());
    }

    public function test_password_change_rejects_wrong_current_weak_and_unchanged_passwords(): void
    {
        [, $res] = $this->createViaApi();
        $token = $this->loginToken('enum.new@kfhi.test', self::TEMP);

        $call = fn (array $body) => $this->withFreshToken($token)->postJson('/api/change-password', $body);

        $call(['current_password' => 'Wrong@12345', 'password' => self::NEW, 'password_confirmation' => self::NEW])
            ->assertStatus(422)->assertJsonValidationErrors('current_password');

        $call(['current_password' => self::TEMP, 'password' => 'weak', 'password_confirmation' => 'weak'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $call(['current_password' => self::TEMP, 'password' => self::NEW, 'password_confirmation' => 'Different@1'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $call(['current_password' => self::TEMP, 'password' => self::TEMP, 'password_confirmation' => self::TEMP])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertTrue(User::find($res->json('id'))->must_change_password);
        $this->assertSame(1, AuditLog::where('action', 'PASSWORD_CHANGE_FAILED')->count());
    }

    public function test_admin_password_reset_forces_a_change_and_signs_the_user_out(): void
    {
        $admin = $this->makeUser('admin');
        $user  = $this->makeUser('field_officer');
        $old   = $user->createToken('old')->plainTextToken;

        $this->asUser($admin)->putJson("/api/users/{$user->id}", ['password' => self::TEMP])->assertOk()
            ->assertJsonPath('must_change_password', true);

        $this->assertTrue($user->fresh()->must_change_password);
        $this->withFreshToken($old)->getJson('/api/me')->assertUnauthorized();
        $this->assertSame(1, AuditLog::where('action', 'RESET_USER_PASSWORD')->where('entity_id', $user->id)->count());
        $this->assertSame(0, AuditLog::where('details', 'like', '%' . self::TEMP . '%')->count());
    }

    public function test_existing_accounts_are_not_forced_to_change_their_password(): void
    {
        $user = $this->makeUser('child_officer');

        $this->assertFalse($user->fresh()->must_change_password);
        $this->asUser($user)->getJson('/api/children')->assertOk();
    }

    public function test_only_admin_can_reset_or_edit_profile_fields(): void
    {
        $target = $this->makeUser('field_officer');

        foreach (['child_officer', 'field_officer', 'survey_enumerator'] as $role) {
            $this->asUser($this->makeUser($role))
                ->putJson("/api/users/{$target->id}", ['phone' => '0770000000', 'password' => self::TEMP])
                ->assertForbidden();
        }

        $this->assertNull($target->fresh()->phone);
        $this->assertFalse($target->fresh()->must_change_password);
    }
}