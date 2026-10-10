<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $fillable = ['child_id','name','relationship','contact_number','address'];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
