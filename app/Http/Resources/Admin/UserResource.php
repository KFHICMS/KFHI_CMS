<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,

            // Each user has one role. Shown only if roles were loaded.
            'role'                 => $this->whenLoaded('roles', fn () => $this->roles->first()?->name),

            'is_active'            => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'last_login_at'        => $this->last_login_at?->toIso8601String(),
            'created_at'           => $this->created_at?->toIso8601String(),

            // Staff details (null if the user has no profile yet).
            'staff_profile'        => $this->whenLoaded('staffProfile', fn () => $this->staffProfile ? [
                'emp_no'      => $this->staffProfile->emp_no,
                'position'    => $this->staffProfile->position,
                'address'     => $this->staffProfile->address,
                'dob'         => $this->staffProfile->dob?->toDateString(),
                'phone'       => $this->staffProfile->phone,
                'joined_date' => $this->staffProfile->joined_date?->toDateString(),
                'photo_url'   => $this->staffProfile->photo_path
                    ? url("/api/admin/users/{$this->id}/photo") . '?v=' . $this->staffProfile->updated_at?->timestamp
                    : null,
            ] : null),
        ];
    }
}