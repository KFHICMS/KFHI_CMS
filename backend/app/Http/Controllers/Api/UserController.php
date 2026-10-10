<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // GET /api/users
    public function index()
    {
        return response()->json(
            User::with('programs:id')->latest()->get()->map(fn ($u) => $this->transform($u))
        );
    }

    // GET /api/roles
    public function roles()
    {
        return response()->json(Role::orderBy('name')->pluck('name'));
    }

    // POST /api/users
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'password'      => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
            'role'          => ['required', 'string', Rule::in(Role::pluck('name')->toArray())],
            'program_ids'   => ['sometimes', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],   // hashed by the model cast
            'is_active' => true,
        ]);
        $user->assignRole($data['role']);
        if (isset($data['program_ids'])) {
            $user->programs()->sync($data['program_ids']);
        }

        $this->log($request, 'CREATE_USER', $user->id);

        return response()->json($this->transform($user->load('programs:id')), 201);
    }

    // PUT /api/users/{user}
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'email'         => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'      => ['sometimes', 'string', Password::min(8)->mixedCase()->numbers()],
            'role'          => ['sometimes', 'string', Rule::in(Role::pluck('name')->toArray())],
            'program_ids'   => ['sometimes', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ]);

        if (isset($data['name']))  $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        if (isset($data['password'])) {
            $user->password = $data['password'];
            $user->tokens()->delete();          // force re-login after a password reset
        }
        $user->save();

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }
        if (array_key_exists('program_ids', $data)) {
            $user->programs()->sync($data['program_ids']);
        }

        $this->log($request, 'UPDATE_USER', $user->id);

        return response()->json($this->transform($user->fresh()->load('programs:id')));
    }

    // PATCH /api/users/{user}/deactivate
    public function deactivate(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete();   // revoke all their tokens immediately

        $this->log($request, 'DEACTIVATE_USER', $user->id);
        return response()->json(['message' => 'User deactivated']);
    }

    // PATCH /api/users/{user}/activate
    public function activate(Request $request, User $user)
    {
        $user->update(['is_active' => true]);
        $this->log($request, 'ACTIVATE_USER', $user->id);
        return response()->json(['message' => 'User activated']);
    }

    // ---- helpers ----
    private function transform(User $u): array
    {
        return [
            'id'          => $u->id,
            'name'        => $u->name,
            'email'       => $u->email,
            'is_active'   => $u->is_active,
            'roles'       => $u->getRoleNames(),
            'program_ids' => $u->programs->pluck('id'),
        ];
    }

    private function log(Request $request, string $action, int $id): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'User',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
        ]);
    }
}