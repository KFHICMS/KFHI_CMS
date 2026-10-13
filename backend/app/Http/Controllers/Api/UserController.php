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
        $this->normaliseUsername($request);

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'username'      => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'password'      => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
            'must_change_password' => ['sometimes', 'boolean'],
            'role'          => ['required', 'string', Rule::in(Role::pluck('name')->toArray())],
            'program_ids'   => ['sometimes', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'username'  => $data['username'] ?? $this->uniqueUsername($data['email']),
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => $data['password'],   // hashed by the model cast
            'is_active' => true,
            'must_change_password' => $data['must_change_password'] ?? true,
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
        $this->normaliseUsername($request);

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'username'      => ['sometimes', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email'         => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'password'      => ['sometimes', 'string', Password::min(8)->mixedCase()->numbers()],
            'role'          => ['sometimes', 'string', Rule::in(Role::pluck('name')->toArray())],
            'program_ids'   => ['sometimes', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ]);

        if (isset($data['name']))     $user->name = $data['name'];
        if (isset($data['email']))    $user->email = $data['email'];
        if (isset($data['username'])) $user->username = $data['username'];
        if (array_key_exists('phone', $data)) $user->phone = $data['phone'];

        $passwordReset = isset($data['password']);
        if ($passwordReset) {
            $user->password = $data['password'];
            // admin-set password is temporary, unless the admin edits their own account
            $user->must_change_password = $user->id !== $request->user()->id;
            $user->tokens()->delete();
        }
        $user->save();

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }
        if (array_key_exists('program_ids', $data)) {
            $user->programs()->sync($data['program_ids']);
        }

        $this->log($request, 'UPDATE_USER', $user->id);
        if ($passwordReset) {
            $this->log($request, 'RESET_USER_PASSWORD', $user->id);
        }

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

        private function transform(User $u): array
    {
        return [
            'id'          => $u->id,
            'name'        => $u->name,
            'username'    => $u->username,
            'email'       => $u->email,
            'phone'       => $u->phone,
            'has_photo'   => (bool) $u->profile_photo_path,
            'is_active'   => $u->is_active,
            'must_change_password' => (bool) $u->must_change_password,
            'date_joined' => $u->created_at,
            'roles'       => $u->getRoleNames(),
            'program_ids' => $u->programs->pluck('id'),
        ];
    }
        private function normaliseUsername(Request $request): void
    {
        if ($request->has('username') && is_string($request->input('username'))) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }
    }

    private function uniqueUsername(string $email): string
    {
        $base = strtolower(preg_replace('/[^A-Za-z0-9._-]/', '', strstr($email, '@', true) ?: $email));
        $base = substr($base !== '' ? $base : 'user', 0, 40);

        $name = $base;
        for ($i = 2; User::where('username', $name)->exists(); $i++) {
            $name = $base . $i;
        }

        return $name;
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