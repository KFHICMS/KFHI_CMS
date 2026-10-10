<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local'); // a temporary fake disk: no real files are touched

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

    private function makeStaff(string $role, string $empNo): User
    {
        $user = User::factory()->create(['email' => strtolower($empNo) . '@kfhi.test']);
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

    private function upload(User $target, UploadedFile $file)
    {
        return $this->actingAsApi($this->admin)->post(
            "/api/admin/users/{$target->id}/photo",
            ['photo' => $file],
            ['Accept' => 'application/json']
        );
    }

    // ---------- Tests ----------

    public function test_admin_can_upload_a_photo(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-500');

        $response = $this->upload($target, UploadedFile::fake()->image('me.jpg', 300, 300))
            ->assertOk()
            ->assertJsonMissingPath('data.staff_profile.photo_path'); // the stored path is never exposed

        $this->assertNotNull($response->json('data.staff_profile.photo_url'));

        $path = $target->fresh()->staffProfile->photo_path;
        $this->assertStringStartsWith('staff-photos/', $path);
        $this->assertNotSame('staff-photos/me.jpg', $path); // random server-made name
        Storage::disk('local')->assertExists($path);

        $this->assertDatabaseHas('audit_logs', [
            'action'    => 'USER_PHOTO_UPDATED',
            'entity_id' => $target->id,
            'user_id'   => $this->admin->id,
        ]);
    }

    public function test_non_admin_cannot_use_the_photo_routes(): void
    {
        $officer = $this->makeStaff('child_officer', 'EMP-501');
        $target  = $this->makeStaff('field_officer', 'EMP-502');

        $this->actingAsApi($officer)->getJson("/api/admin/users/{$target->id}/photo")->assertStatus(403);
        $this->actingAsApi($officer)->post(
            "/api/admin/users/{$target->id}/photo",
            ['photo' => UploadedFile::fake()->image('a.jpg', 300, 300)],
            ['Accept' => 'application/json']
        )->assertStatus(403);
        $this->actingAsApi($officer)->deleteJson("/api/admin/users/{$target->id}/photo")->assertStatus(403);

        $this->assertNull($target->fresh()->staffProfile->photo_path);
    }

    public function test_guest_cannot_view_a_photo(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-503');

        $this->getJson("/api/admin/users/{$target->id}/photo")->assertStatus(401);
    }

    public function test_dangerous_or_wrong_files_are_rejected(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-504');

        $bad = [
            'pdf'      => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'php'      => UploadedFile::fake()->create('shell.php', 10, 'text/x-php'),
            'svg'      => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'fake_jpg' => UploadedFile::fake()->createWithContent('photo.jpg', 'this is not an image'),
            'huge'     => UploadedFile::fake()->image('big.jpg', 300, 300)->size(3000),
            'tiny'     => UploadedFile::fake()->image('tiny.jpg', 50, 50),
        ];

        foreach ($bad as $name => $file) {
            $this->upload($target, $file)
                ->assertStatus(422)
                ->assertJsonValidationErrors(['photo']);
        }

        $this->assertNull($target->fresh()->staffProfile->photo_path);
    }

    public function test_admin_can_view_the_photo_with_safe_headers(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-505');
        $this->upload($target, UploadedFile::fake()->image('me.jpg', 300, 300))->assertOk();

        $response = $this->actingAsApi($this->admin)
            ->get("/api/admin/users/{$target->id}/photo")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
    }

    public function test_viewing_a_missing_photo_returns_404(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-506');

        $this->actingAsApi($this->admin)
            ->getJson("/api/admin/users/{$target->id}/photo")
            ->assertStatus(404);
    }

    public function test_uploading_again_replaces_and_deletes_the_old_file(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-507');

        $this->upload($target, UploadedFile::fake()->image('one.jpg', 300, 300))->assertOk();
        $oldPath = $target->fresh()->staffProfile->photo_path;

        $this->upload($target, UploadedFile::fake()->image('two.jpg', 300, 300))->assertOk();
        $newPath = $target->fresh()->staffProfile->photo_path;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_admin_can_remove_a_photo(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-508');
        $this->upload($target, UploadedFile::fake()->image('me.jpg', 300, 300))->assertOk();
        $path = $target->fresh()->staffProfile->photo_path;

        $this->actingAsApi($this->admin)
            ->deleteJson("/api/admin/users/{$target->id}/photo")
            ->assertOk()
            ->assertJsonPath('data.staff_profile.photo_url', null);

        $this->assertNull($target->fresh()->staffProfile->photo_path);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_PHOTO_REMOVED', 'entity_id' => $target->id]);

        // Removing again gives 404.
        $this->actingAsApi($this->admin)
            ->deleteJson("/api/admin/users/{$target->id}/photo")
            ->assertStatus(404);
    }

    public function test_user_without_a_staff_profile_cannot_get_a_photo(): void
    {
        // The seeded admin has no staff profile.
        $this->upload($this->admin, UploadedFile::fake()->image('me.jpg', 300, 300))
            ->assertStatus(422);
    }

    public function test_the_normal_update_cannot_change_the_photo_path(): void
    {
        $target = $this->makeStaff('field_officer', 'EMP-509');
        $this->upload($target, UploadedFile::fake()->image('me.jpg', 300, 300))->assertOk();
        $path = $target->fresh()->staffProfile->photo_path;

        $this->actingAsApi($this->admin)->putJson("/api/admin/users/{$target->id}", [
            'name'        => $target->name,
            'email'       => $target->email,
            'role'        => 'field_officer',
            'emp_no'      => 'EMP-509',
            'position'    => 'Senior Field Officer',
            'address'     => '12 Main Street, Colombo',
            'dob'         => '1995-05-10',
            'phone'       => '+94 77 123 4567',
            'joined_date' => '2022-01-15',
            'photo_path'  => 'evil/secret.txt',   // attack: try to point to another file
        ])->assertOk();

        $this->assertSame($path, $target->fresh()->staffProfile->photo_path);
    }
}