<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    protected $fillable = ['child_id', 'benefit_type_id', 'event_id', 'quantity', 'notes', 'given_by', 'given_at'];
    protected $casts = ['given_at' => 'datetime'];

    public function child() { return $this->belongsTo(Child::class); }
    public function type()  { return $this->belongsTo(BenefitType::class, 'benefit_type_id'); }
    public function event() { return $this->belongsTo(Event::class); }
}
