<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name', 'description', 'event_date', 'program_id', 'start_at', 'end_at', 'location',
        'qr_attendance_enabled', 'has_questionnaire', 'status',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'start_at'              => 'datetime',
        'end_at'                => 'datetime',
        'qr_attendance_enabled' => 'boolean',
        'has_questionnaire'     => 'boolean',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /** Officers / enumerators assigned to this event. */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Enumerators are temporary users: they only reach events they are assigned to.
     * Admin, child officers and field officers are not restricted by assignment
     * (field officers are still limited to their programs' children).
     */
    public function scopeAccessibleTo(Builder $query, $user): Builder
    {
        if ($user->hasAnyRole(['admin', 'child_officer', 'field_officer'])) {
            return $query;
        }

        if ($user->hasRole('survey_enumerator')) {
            return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

        return $query;
    }

    public function isAccessibleTo($user): bool
    {
        return static::whereKey($this->id)->accessibleTo($user)->exists();
    }
}