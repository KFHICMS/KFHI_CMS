<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name', 'description', 'event_date',
        'qr_attendance_enabled', 'has_questionnaire', 'status',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'qr_attendance_enabled' => 'boolean',
        'has_questionnaire'     => 'boolean',
    ];

    public function attendances()
{
    return $this->hasMany(Attendance::class);
}

}
