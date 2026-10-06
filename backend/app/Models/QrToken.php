<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrToken extends Model
{
    protected $fillable = ['child_id', 'token', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
