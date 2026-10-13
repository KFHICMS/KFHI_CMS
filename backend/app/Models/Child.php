<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = [
        // identity
        'child_code', 'full_name', 'name_korean', 'alias', 'date_of_birth', 'gender', 'religion',
        'address', 'area', 'gnd', 'photo_path',
        // office / sponsorship
        'office_code', 'office_name', 'service_state', 'sponsor_state', 'guardian_type', 'caregiver',
        // education
        'school', 'curriculum', 'grade', 'education_status', 'favorite_subject', 'pass_fail',
        // interests
        'dream', 'dream_description', 'favorite_activity',
        // sensitive (encrypted)
        'health', 'medical_info', 'special_requirements',
        'disability_type', 'disability_description', 'emergency_contacts',
        // program / lifecycle
        'program', 'registration_date', 'participation_details', 'status',
    ];

    protected $casts = [
        'date_of_birth'          => 'date',
        'registration_date'      => 'date',
        'health'                 => 'encrypted',   // 🔒 encrypted at rest
        'medical_info'           => 'encrypted',   // 🔒 (= "Health Description")
        'special_requirements'   => 'encrypted',   // 🔒
        'disability_type'        => 'encrypted',   // 🔒
        'disability_description' => 'encrypted',   // 🔒
        'emergency_contacts'     => 'encrypted',   // 🔒
    ];

    public function guardians() { return $this->hasMany(Guardian::class); }
    public function benefits()  { return $this->hasMany(Benefit::class); }
    public function followUps() { return $this->hasMany(FollowUp::class); }

    /**
     * Age is NEVER stored: it is calculated from the birthday so it can't go out of date.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Program scoping (proposal 7.1): field roles only reach children in
     * programs they are assigned to. A field user with NO assigned programs
     * sees nothing. Admin / child officer are not restricted.
     */
    public function scopeAccessibleTo(Builder $query, $user): Builder
    {
        if ($user->hasAnyRole(['admin', 'child_officer'])) {
            return $query;
        }

        if ($user->hasAnyRole(['field_officer', 'survey_enumerator'])) {
            $names = $user->programs()->pluck('programs.name');
            return $query->whereIn('program', $names);   // empty list => no rows
        }

        return $query;
    }

    public function isAccessibleTo($user): bool
    {
        return static::whereKey($this->id)->accessibleTo($user)->exists();
    }

    /**
     * Return only the fields the given user's role is allowed to see.
     * Note: the API key `medical_info` is shown in the UI as "Health description".
     */
    public function visibleTo($user): array
    {
        $data = [
            'id'                    => $this->id,
            'child_code'            => $this->child_code,
            'full_name'             => $this->full_name,
            'name_korean'           => $this->name_korean,
            'alias'                 => $this->alias,
            'date_of_birth'         => $this->date_of_birth,
            'age'                   => $this->age,
            'gender'                => $this->gender,
            'religion'              => $this->religion,
            'address'               => $this->address,
            'area'                  => $this->area,
            'gnd'                   => $this->gnd,
            'has_photo'             => (bool) $this->photo_path,
            'office_code'           => $this->office_code,
            'office_name'           => $this->office_name,
            'service_state'         => $this->service_state,
            'sponsor_state'         => $this->sponsor_state,
            'guardian_type'         => $this->guardian_type,
            'caregiver'             => $this->caregiver,
            'school'                => $this->school,
            'curriculum'            => $this->curriculum,
            'grade'                 => $this->grade,
            'education_status'      => $this->education_status,
            'favorite_subject'      => $this->favorite_subject,
            'pass_fail'             => $this->pass_fail,
            'dream'                 => $this->dream,
            'dream_description'     => $this->dream_description,
            'favorite_activity'     => $this->favorite_activity,
            'program'               => $this->program,
            'registration_date'     => $this->registration_date,
            'participation_details' => $this->participation_details,
            'status'                => $this->status,
            'guardians'             => $this->guardians,
        ];

        if ($user->can('view_child_health')) {
            $data['health']                 = $this->health;
            $data['medical_info']           = $this->medical_info;
            $data['special_requirements']   = $this->special_requirements;
            $data['disability_type']        = $this->disability_type;
            $data['disability_description'] = $this->disability_description;
        }
        if ($user->can('view_child_emergency')) {
            $data['emergency_contacts'] = $this->emergency_contacts;
        }

        return $data;
    }
}