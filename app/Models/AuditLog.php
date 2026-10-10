<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;   // append-only; DB sets created_at

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'details',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
