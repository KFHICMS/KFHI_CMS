<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        Message::create([
            'sender_id' => Auth::id() ?? 1, // Fallback for demo
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', 'Message sent successfully.');
    }

    public function destroy(Message $message)
    {
        if ($message->receiver_id == Auth::id() || Auth::id() == null) {
            $message->delete();
            return redirect()->back()->with('success', 'Message deleted successfully.');
        }

        return redirect()->back()->with('error', 'Unauthorized.');
    }
}
