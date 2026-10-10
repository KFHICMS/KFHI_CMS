<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BenefitType extends Model
{
    protected $fillable = ['name', 'description'];
    public function benefits() { return $this->hasMany(Benefit::class); }
}
