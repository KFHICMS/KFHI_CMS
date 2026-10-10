<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = [
        'child_code','full_name','date_of_birth','gender','address','photo_path',
        'school','grade','education_status',
        'medical_info','special_requirements','emergency_contacts',
        'program','registration_date','participation_details','status',
    ];

    protected $casts = [
        'date_of_birth'        => 'date',
        'registration_date'    => 'date',
        'medical_info'         => 'encrypted',   // 🔒 encrypted at rest
        'special_requirements' => 'encrypted',   // 🔒
        'emergency_contacts'   => 'encrypted',   // 🔒
    ];

    public function guardians() { return $this->hasMany(Guardian::class); }
    public function benefits()  { return $this->hasMany(Benefit::class); }
    public function followUps() { return $this->hasMany(FollowUp::class); }

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
     */
    public function visibleTo($user): array
    {
        $data = [
            'id'                    => $this->id,
            'child_code'            => $this->child_code,
            'full_name'             => $this->full_name,
            'date_of_birth'         => $this->date_of_birth,
            'gender'                => $this->gender,
            'address'               => $this->address,
            'has_photo'             => (bool) $this->photo_path,
            'school'                => $this->school,
            'grade'                 => $this->grade,
            'education_status'      => $this->education_status,
            'program'               => $this->program,
            'registration_date'     => $this->registration_date,
            'participation_details' => $this->participation_details,
            'status'                => $this->status,
            'guardians'             => $this->guardians,
        ];

        if ($user->can('view_child_health')) {
            $data['medical_info']         = $this->medical_info;
            $data['special_requirements'] = $this->special_requirements;
        }
        if ($user->can('view_child_emergency')) {
            $data['emergency_contacts'] = $this->emergency_contacts;
        }

        return $data;
    }
}