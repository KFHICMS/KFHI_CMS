<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET /api/admin/users?search=&role=&status=&per_page=
    public function index(Request $request)
    {
        // Validate the filters too. Never trust any input, even in a GET request.
        $filters = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'role'     => ['nullable', 'string', Rule::exists('roles', 'name')],
            'status'   => ['nullable', 'in:active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $users = User::query()
            ->with(['roles', 'staffProfile'])

            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('staffProfile', function ($s) use ($search) {
                          $s->where('emp_no', 'like', "%{$search}%")
                            ->orWhere('position', 'like', "%{$search}%");
                      });
                });
            })

            ->when($filters['role'] ?? null, fn ($query, $role) => $query->role($role))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))

            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return UserResource::collection($users);
    }

    // GET /api/admin/users/{user}
    public function show(User $user)
    {
        $user->load(['roles', 'staffProfile']);

        return new UserResource($user);
    }

    // POST /api/admin/users
    public function store(StoreUserRequest $request)
    {
        // Data is already validated and cleaned by StoreUserRequest.
        $data = $request->validated();

        // Random 14-character password made by the server.
        $temporaryPassword = Str::password(14);

        // All-or-nothing: if any step fails, nothing is saved.
        $user = DB::transaction(function () use ($data, $temporaryPassword, $request) {

            // We set the sensitive fields one by one (not mass assignment).
            $user = new User();
            $user->name                 = $data['name'];
            $user->email                = $data['email'];
            $user->password             = $temporaryPassword; // auto-hashed by the model
            $user->is_active            = true;
            $user->must_change_password = true;
            $user->save();

            $user->assignRole($data['role']);

            // user_id is set automatically by the relationship.
            $user->staffProfile()->create([
                'emp_no'      => $data['emp_no'],
                'position'    => $data['position'],
                'address'     => $data['address'],
                'dob'         => $data['dob'],
                'phone'       => $data['phone'],
                'joined_date' => $data['joined_date'],
            ]);

            // Audit log: who did what. NEVER put the password here.
            AuditLog::create([
                'user_id'     => $request->user()->id,
                'action'      => 'USER_CREATED',
                'entity_type' => 'User',
                'entity_id'   => $user->id,
                'ip_address'  => $request->ip(),
                'details'     => "Created user {$user->email} with role {$data['role']}",
            ]);

            return $user;
        });

        $user->load(['roles', 'staffProfile']);

        return (new UserResource($user))
            ->additional([
                // Shown only this one time. It is not stored in readable form.
                'temporary_password' => $temporaryPassword,
                'message'            => 'User created. Give the temporary password to the staff member. It will not be shown again.',
            ])
            ->response()
            ->setStatusCode(201)
            ->header('Cache-Control', 'no-store'); // browsers must not cache the password
    }


    // PUT /api/admin/users/{user}
    public function update(UpdateUserRequest $request, User $user)
    {
        $data        = $request->validated();
        $newRole     = $data['role'];
        $currentRole = $user->getRoleNames()->first();

        // Safety rule: an admin cannot change his own role.
        if ($newRole !== $currentRole && $request->user()->is($user)) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        DB::transaction(function () use ($request, $user, $data, $newRole, $currentRole) {
            // Basic user fields
            $user->name  = $data['name'];
            $user->email = $data['email'];
            $changed     = array_keys($user->getDirty()); // which fields really changed
            $user->save();

            // Role
            if ($newRole !== $currentRole) {
                $user->syncRoles([$newRole]);
                $changed[] = "role ({$currentRole} -> {$newRole})";
            }

            // Staff profile (created if it does not exist yet)
            $profile = $user->staffProfile()->firstOrNew([]);
            $profile->fill([
                'emp_no'      => $data['emp_no'],
                'position'    => $data['position'],
                'address'     => $data['address'],
                'dob'         => $data['dob'],
                'phone'       => $data['phone'],
                'joined_date' => $data['joined_date'],
            ]);
            foreach (array_keys($profile->getDirty()) as $field) {
                $changed[] = "staff.{$field}";
            }
            $profile->save();

            // Audit log: only if something really changed. Field names only, no values.
            if ($changed) {
                AuditLog::create([
                    'user_id'     => $request->user()->id,
                    'action'      => 'USER_UPDATED',
                    'entity_type' => 'User',
                    'entity_id'   => $user->id,
                    'ip_address'  => $request->ip(),
                    'details'     => 'Updated ' . $user->email . ': ' . implode(', ', $changed),
                ]);
            }
        });

        $user->load(['roles', 'staffProfile']);

        return new UserResource($user);
    }


    // PATCH /api/admin/users/{user}/deactivate
    public function deactivate(Request $request, User $user)
    {
        // Rule 1: nobody can deactivate himself.
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        // Rule 2: at least one other active admin must remain.
        $otherActiveAdmins = User::role('admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($user->hasRole('admin') && ! $otherActiveAdmins) {
            return response()->json(['message' => 'You cannot deactivate the last active admin.'], 422);
        }

        // Only act if the user is active now (no duplicate log lines).
        if ($user->is_active) {
            DB::transaction(function () use ($request, $user) {
                $user->is_active = false;
                $user->save();

                // Log the user out everywhere, immediately.
                $user->tokens()->delete();

                AuditLog::create([
                    'user_id'     => $request->user()->id,
                    'action'      => 'USER_DEACTIVATED',
                    'entity_type' => 'User',
                    'entity_id'   => $user->id,
                    'ip_address'  => $request->ip(),
                    'details'     => "Deactivated user {$user->email}",
                ]);
            });
        }

        $user->load(['roles', 'staffProfile']);

        return new UserResource($user);
    }

    // PATCH /api/admin/users/{user}/activate
    public function activate(Request $request, User $user)
    {
        if (! $user->is_active) {
            DB::transaction(function () use ($request, $user) {
                $user->is_active = true;
                $user->save();

                AuditLog::create([
                    'user_id'     => $request->user()->id,
                    'action'      => 'USER_ACTIVATED',
                    'entity_type' => 'User',
                    'entity_id'   => $user->id,
                    'ip_address'  => $request->ip(),
                    'details'     => "Activated user {$user->email}",
                ]);
            });
        }

        $user->load(['roles', 'staffProfile']);

        return new UserResource($user);
    }



    // POST /api/admin/users/{user}/reset-password
    public function resetPassword(Request $request, User $user)
    {
        // An admin must use the normal "change password" page for himself.
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'You cannot reset your own password here.'], 422);
        }

        $temporaryPassword = Str::password(14);

        DB::transaction(function () use ($request, $user, $temporaryPassword) {
            $user->password             = $temporaryPassword; // auto-hashed by the model
            $user->must_change_password = true;
            $user->save();

            // Kill every open session of this user.
            $user->tokens()->delete();

            // NEVER put the password in the log.
            AuditLog::create([
                'user_id'     => $request->user()->id,
                'action'      => 'USER_PASSWORD_RESET',
                'entity_type' => 'User',
                'entity_id'   => $user->id,
                'ip_address'  => $request->ip(),
                'details'     => "Reset password for {$user->email}",
            ]);
        });

        $user->load(['roles', 'staffProfile']);

        return (new UserResource($user))
            ->additional([
                'temporary_password' => $temporaryPassword,
                'message'            => 'Password reset. Give the temporary password to the staff member. It will not be shown again.',
            ])
            ->response()
            ->header('Cache-Control', 'no-store');
    }
}