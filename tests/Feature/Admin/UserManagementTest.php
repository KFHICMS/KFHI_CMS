<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    // Every test starts with a fresh, empty, temporary database.
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles, permissions and the admin user (same seeder as the real project).
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::where('email', 'admin@kfhi.test')->firstOrFail();
    }

    // ---------- Helpers (small tools used by many tests) ----------

    // Send the next request as this user, using a real Sanctum token.
    private function actingAsApi(User $user): static
    {
        app('auth')->forgetGuards(); // forget the previous user
        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    // Create a user with a role and a staff profile directly in the database.
    private function makeStaff(string $role, string $empNo, array $userAttrs = []): User
    {
        $user = User::factory()->create(array_merge(
            ['email' => strtolower($empNo) . '@kfhi.test'],
            $userAttrs
        ));
        $user->assignRole($role);
        $user->staffProfile()->create([
            'emp_no'      => $empNo,
            'position'    => 'Field Officer',
            'address'     => '12 Main Street, Colombo',
            'dob'         => '1995-05-10',
            'phone'       => '+94 77 123 4567',
            'joined_date' => '2022-01-15',
        ]);

        return $user;
    }

    // A valid request body for create / update.
    private function payload(array $override = []): array
    {
        return array_merge([
            'name'        => 'New Officer',
            'email'       => 'new.officer@kfhi.test',
            'role'        => 'field_officer',
            'emp_no'      => 'EMP-100',
            'position'    => 'Field Officer',
            'address'     => '12 Main Street, Colombo',
            'dob'         => '1995-05-10',
            'phone'       => '+94 77 123 4567',
            'joined_date' => '2022-01-15',
        ], $override);
    }

    // ---------- Access control ----------

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_non_admin_is_blocked_from_every_admin_route(): void
    {
        $officer = $this->makeStaff('child_officer', 'EMP-200');
        $target  = $this->makeStaff('field_officer', 'EMP-201');

        $routes = [
            ['GET',   '/api/admin/users'],
            ['GET',   "/api/admin/users/{$target->id}"],
            ['POST',  '/api/admin/users'],
            ['PUT',   "/api/admin/users/{$target->id}"],
            ['PATCH', "/api/admin/users/{$target->id}/deactivate"],
            ['PATCH', "/api/admin/users/{$target->id}/activate"],
            ['POST',  "/api/admin/users/{$target->id}/reset-password"],
        ];

        foreach ($routes as [$method, $uri]) {
            $this->actingAsApi($officer)->json($method, $uri, [])->assertStatus(403);
        }

        // Nothing was changed.
        $this->assertTrue((bool) $target->fresh()->is_active);
    }

    // ---------- Create ----------

    public function test_admin_can_create_a_user(): void
    {
        $response = $this->actingAsApi($this->admin)->postJson(
            '/api/admin/users',
            $this->payload(['email' => 'New.Officer@KFHI.test', 'emp_no' => 'emp-100'])
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'new.officer@kfhi.test')   // lowercased
            ->assertJsonPath('data.role', 'field_officer')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.staff_profile.emp_no', 'EMP-100')  // uppercased
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'is_active', 'staff_profile'], 'temporary_password'])
            ->assertJsonMissingPath('data.password');

        $temp = $response->json('temporary_password');
        $user = User::where('email', 'new.officer@kfhi.test')->firstOrFail();

        // The password is stored hashed, never as plain text.
        $this->assertTrue(Hash::check($temp, $user->password));
        $this->assertNotSame($temp, $user->password);
        $this->assertTrue($user->hasRole('field_officer'));

        // Audit log exists and does NOT contain the password.
        $this->assertDatabaseHas('audit_logs', [
            'action'    => 'USER_CREATED',
            'entity_id' => $user->id,
            'user_id'   => $this->admin->id,
        ]);
        $log = AuditLog::where('action', 'USER_CREATED')->firstOrFail();
        $this->assertStringNotContainsString($temp, $log->details);
    }

    public function test_extra_dangerous_fields_are_ignored_when_creating(): void
    {
        $response = $this->actingAsApi($this->admin)->postJson(
            '/api/admin/users',
            $this->payload([
                'password'             => 'Hacker123!',
                'is_active'            => false,
                'must_change_password' => false,
                'id'                   => 999,
            ])
        );

        $response->assertStatus(201);

        $user = User::where('email', 'new.officer@kfhi.test')->firstOrFail();
        $this->assertNotSame(999, $user->id);
        $this->assertTrue((bool) $user->is_active);
        $this->assertTrue((bool) $user->must_change_password);
        $this->assertFalse(Hash::check('Hacker123!', $user->password));
    }

    public function test_create_rejects_invalid_data(): void
    {
        $this->actingAsApi($this->admin)->postJson('/api/admin/users', [
            'name'        => 'X',
            'email'       => 'bad',
            'role'        => 'superman',
            'emp_no'      => 'A B',
            'position'    => 'x',
            'address'     => 'x',
            'dob'         => '2015-01-01',
            'phone'       => 'abc',
            'joined_date' => '2030-01-01',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email', 'role', 'emp_no', 'dob', 'phone', 'joined_date']);

        $this->assertDatabaseMissing('users', ['name' => 'X']);
    }

    public function test_create_rejects_duplicate_email_and_emp_no(): void
    {
        $this->makeStaff('field_officer', 'EMP-100', ['email' => 'new.officer@kfhi.test']);

        $this->actingAsApi($this->admin)
            ->postJson('/api/admin/users', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'emp_no']);
    }

    public function test_create_rejects_staff_under_18(): void
    {
        $under18 = now()->subYears(17)->toDateString();

        $this->actingAsApi($this->admin)
            ->postJson('/api/admin/users', $this->payload(['dob' => $under18]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dob']);
    }

    // ---------- List and search ----------

    public function test_list_supports_search_role_and_status_filters(): void
    {
        $this->makeStaff('field_officer', 'EMP-300');
        $this->makeStaff('child_officer', 'EMP-301');
        $off = $this->makeStaff('field_officer', 'EMP-302');
        $off->forceFill(['is_active' => false])->save();

        // Search by employee number
        $this->actingAsApi($this->admin)
            ->getJson('/api/admin/users?search=EMP-300')
            ->assertOk()->assertJsonCount(1, 'data');

        // Filter by role (2 field officers: EMP-300 and EMP-302)
        $this->actingAsApi($this->admin)
            ->getJson('/api/admin/users?role=field_officer')
            ->assertOk()->assertJsonCount(2, 'data');

        // Filter by status
        $this->actingAsApi($this->admin)
            ->getJson('/api/admin/users?status=inactive')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_list_rejects_a_huge_page_size(): void
    {
        $this->actingAsApi($this->admin)
            ->getJson('/api/admin/users?per_page=100')
            ->assertStatus(422);
    }

    // ---------- Update ----------

    public function test_admin_can_update_a_user_and_the_change_is_logged(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-400');

        $this->actingAsApi($this->admin)->putJson("/api/admin/users/{$target->id}", $this->payload([
            'name'     => $target->name,
            'email'    => $target->email,
            'emp_no'   => 'EMP-400',
            'position' => 'Senior Field Officer',
            'role'     => 'child_officer',
        ]))->assertOk()
           ->assertJsonPath('data.staff_profile.position', 'Senior Field Officer')
           ->assertJsonPath('data.role', 'child_officer');

        $this->assertTrue($target->fresh()->hasRole('child_officer'));
        $this->assertDatabaseHas('staff_profiles', [
            'user_id'  => $target->id,
            'position' => 'Senior Field Officer',
        ]);

        $log = AuditLog::where('action', 'USER_UPDATED')->firstOrFail();
        $this->assertStringContainsString('staff.position', $log->details);
        $this->assertStringNotContainsString('Main Street', $log->details); // no personal data in the log
    }

    public function test_update_with_unchanged_email_and_emp_no_is_allowed(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-410');

        $this->actingAsApi($this->admin)->putJson("/api/admin/users/{$target->id}", $this->payload([
            'name'   => $target->name,
            'email'  => $target->email,
            'emp_no' => 'EMP-410',
        ]))->assertOk();
    }

    public function test_admin_cannot_change_his_own_role(): void
    {
        $this->actingAsApi($this->admin)->putJson("/api/admin/users/{$this->admin->id}", $this->payload([
            'name'   => $this->admin->name,
            'email'  => $this->admin->email,
            'emp_no' => 'ADM-001',
            'role'   => 'field_officer',
        ]))->assertStatus(422);

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_update_rejects_an_email_that_belongs_to_another_user(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-420');

        $this->actingAsApi($this->admin)->putJson("/api/admin/users/{$target->id}", $this->payload([
            'name'   => $target->name,
            'email'  => 'admin@kfhi.test',
            'emp_no' => 'EMP-420',
        ]))->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_update_of_a_missing_user_returns_404(): void
    {
        $this->actingAsApi($this->admin)
            ->putJson('/api/admin/users/9999', $this->payload())
            ->assertStatus(404);
    }
}