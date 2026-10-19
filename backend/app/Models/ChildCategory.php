<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildCategory extends Model
{
    public const OPTIONS = ['education', 'health', 'nutrition', 'shelter'];

    protected $fillable = ['child_id', 'category'];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}