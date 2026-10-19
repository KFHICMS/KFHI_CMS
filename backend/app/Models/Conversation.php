<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['subject', 'created_by'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants');
    }
}