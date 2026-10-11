<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    // Only these fields can be filled in bulk (mass assignment protection).
    // user_id is NOT here on purpose: we set it ourselves in code.
    protected $fillable = [
        'emp_no',
        'position',
        'address',
        'dob',
        'phone',
        'joined_date',
    ];

    // Tell Laravel these two columns are dates.
    protected function casts(): array
    {
        return [
            'dob'         => 'date',
            'joined_date' => 'date',
        ];
    }

    // A staff profile belongs to one user.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}