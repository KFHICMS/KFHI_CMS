<?php

namespace App\Models;

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
    'medical_info'         => 'encrypted',   // 🔒fields are automatically encrypted (AES-256) when saved 
    'special_requirements' => 'encrypted',   // 🔒
    'emergency_contacts'   => 'encrypted',   // 🔒
];


    public function guardians()
    {
        return $this->hasMany(Guardian::class);
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
        'photo_path'            => $this->photo_path,
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
