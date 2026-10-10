<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    // Only users with the manage_users permission may continue.
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    // Clean the data BEFORE validating (remove spaces, fix letter case).
    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (['name', 'email', 'emp_no', 'position', 'address', 'phone'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim($this->input($field));
            }
        }

        if (isset($clean['email'])) {
            $clean['email'] = strtolower($clean['email']);
        }
        if (isset($clean['emp_no'])) {
            $clean['emp_no'] = strtoupper($clean['emp_no']);
        }

        $this->merge($clean);
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],

            // Role must be a real role that exists in the roles table.
            'role'  => ['required', 'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web')],

            'emp_no'      => ['required', 'string', 'max:50',
                'regex:/^[A-Za-z0-9\-\/]+$/', 'unique:staff_profiles,emp_no'],
            'position'    => ['required', 'string', 'max:255'],
            'address'     => ['required', 'string', 'max:1000'],

            // Staff must be at least 18 years old.
            'dob'         => ['required', 'date', 'after:1900-01-01',
                'before_or_equal:' . now()->subYears(18)->toDateString()],

            // Digits, spaces, dash, optional + at the start (7 to 20 characters).
            'phone'       => ['required', 'string', 'max:20',
                'regex:/^\+?[0-9\s\-]{7,20}$/'],

            // Cannot be in the future and cannot be before the birth date.
            'joined_date' => ['required', 'date', 'after:dob', 'before_or_equal:today'],
        ];
    }
}