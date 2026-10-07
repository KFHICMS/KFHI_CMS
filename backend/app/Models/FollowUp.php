<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    protected $table = 'follow_ups';

    protected $fillable = ['child_id', 'title', 'notes', 'status', 'due_date', 'assigned_to', 'created_by', 'completed_at'];
    protected $casts = ['due_date' => 'date', 'completed_at' => 'datetime'];

    public function child()    { return $this->belongsTo(Child::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}
