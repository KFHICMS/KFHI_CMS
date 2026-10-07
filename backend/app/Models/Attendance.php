<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['event_id', 'child_id', 'status', 'marked_by'];

    public function child() { return $this->belongsTo(Child::class); }
    public function event() { return $this->belongsTo(Event::class); }
}
