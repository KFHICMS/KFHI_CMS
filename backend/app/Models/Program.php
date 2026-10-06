<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
}

