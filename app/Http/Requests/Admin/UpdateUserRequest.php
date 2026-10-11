<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

// Same rules as creating, but "unique" must ignore the user we are editing.
// Otherwise saving a user without changing the email would say "email already taken".
class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        // {user} comes from the route (we make it in Step 7).
        $user = $this->route('user');

        $rules = parent::rules();

        $rules['email'] = ['required', 'email:rfc', 'max:255',
            Rule::unique('users', 'email')->ignore($user->id)];

        $rules['emp_no'] = ['required', 'string', 'max:50',
            'regex:/^[A-Za-z0-9\-\/]+$/',
            Rule::unique('staff_profiles', 'emp_no')->ignore($user->staffProfile?->id)];

        return $rules;
    }
}