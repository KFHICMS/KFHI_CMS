<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'last_read_message_id', 'deleted_at'];

    protected $casts = ['deleted_at' => 'datetime'];
}