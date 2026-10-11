<?php

namespace Tests;

use App\Models\Child;
use App\Models\Program;
use App\Models\QrToken;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Base class for KFHI feature tests.
 * Runs on in-memory SQLite only – your real MySQL database is never touched.
 */
abstract class KfhiTestCase extends TestCase
{
    use RefreshDatabase;

    private static int $childCounter = 0;

    /** Safety net: refuse to run if the tests are not on in-memory SQLite. */
    protected function beforeRefreshingDatabase()
    {
        $default = config('database.default');
        $name    = config("database.connections.$default.database");

        if ($default !== 'sqlite' || $name !== ':memory:') {
            throw new \RuntimeException(
                'Refusing to run: tests must use in-memory SQLite (check phpunit.xml). Your real database was NOT touched.'
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ---------- helpers ----------

    /** Create a user with one of the 4 roles, optionally assigned to programs (by name). */
    protected function makeUser(string $role, array $programNames = [], array $attrs = []): User
    {
        $user = User::create(array_merge([
            'name'      => ucfirst(str_replace('_', ' ', $role)) . ' Test',
            'email'     => $role . '.' . uniqid() . '@kfhi.test',
            'password'  => 'Test@12345',
            'is_active' => true,
        ], $attrs));

        $user->assignRole($role);

        if ($programNames) {
            $ids = collect($programNames)->map(fn ($n) => $this->makeProgram($n)->id)->all();
            $user->programs()->sync($ids);
        }

        return $user;
    }

    protected function makeProgram(string $name): Program
    {
        return Program::firstOrCreate(['name' => $name], ['status' => 'active']);
    }

    protected function makeChild(string $program = 'Education', array $attrs = []): Child
    {
        $n = ++self::$childCounter;

        return Child::create(array_merge([
            'child_code'         => sprintf('TST%05d', $n),
            'full_name'          => "Test Child $n",
            'program'            => $program,
            'status'             => 'active',
            'medical_info'       => 'Asthma',
            'emergency_contacts' => '0771234567',
        ], $attrs));
    }

    /** Call the API as this user, using a real Sanctum token (same path as the browser). */
    protected function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();   // never reuse the previous request's user

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    /** An encrypted QR token for a child, exactly as the QR code would carry it. */
    protected function qrTokenFor(Child $child): string
    {
        $reference = Str::random(40);
        QrToken::create(['child_id' => $child->id, 'token' => $reference, 'is_active' => true]);

        return Crypt::encryptString($reference);
    }
}