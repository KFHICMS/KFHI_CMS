<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    // GET /api/messages?folder=inbox|trash
    public function index(Request $request)
    {
        $me    = $request->user()->id;
        $trash = $request->query('folder') === 'trash';

        $conversations = Conversation::query()
            ->whereHas('participants', function ($q) use ($me, $trash) {
                $q->where('user_id', $me);
                $trash ? $q->whereNotNull('deleted_at') : $q->whereNull('deleted_at');
            })
            ->with(['users:id,name', 'latestMessage.sender:id,name'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $unread = $this->unreadIds($me);

        return response()->json($conversations->map(fn ($c) => [
            'id'          => $c->id,
            'subject'     => $c->subject,
            'with'        => $c->users->where('id', '!=', $me)->pluck('name')->values(),
            'last_sender' => $c->latestMessage?->sender?->name,
            'preview'     => Str::limit($c->latestMessage?->body ?? '', 90),
            'last_at'     => $c->latestMessage?->created_at,
            'unread'      => in_array($c->id, $unread),
        ])->values());
    }

    // GET /api/messages/unread-count
    public function unreadCount(Request $request)
    {
        return response()->json(['count' => count($this->unreadIds($request->user()->id))]);
    }

    // GET /api/messages/recipients   (active people you can write to)
    public function recipients(Request $request)
    {
        return response()->json(
            User::where('is_active', true)->where('id', '!=', $request->user()->id)
                ->with('roles:id,name')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->roles->first()?->name])
                ->values()
        );
    }

    // GET /api/messages/{conversation}   (opening it marks it as read)
    public function show(Request $request, Conversation $conversation)
    {
        $row = $this->participant($request, $conversation, true);

        $messages = $conversation->messages()->with('sender:id,name')->orderBy('id')->get();

        if (! $row->deleted_at && $messages->isNotEmpty()) {
            $row->update(['last_read_message_id' => $messages->max('id')]);
        }

        $me = $request->user()->id;

        return response()->json([
            'id'           => $conversation->id,
            'subject'      => $conversation->subject,
            'deleted'      => (bool) $row->deleted_at,
            'participants' => $conversation->users()->get(['users.id', 'users.name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'me' => $u->id === $me])->values(),
            'messages'     => $messages->map(fn ($m) => [
                'id'          => $m->id,
                'sender_name' => $m->sender?->name,
                'mine'        => $m->sender_id === $me,
                'body'        => $m->body,
                'created_at'  => $m->created_at,
            ])->values(),
        ]);
    }

    // POST /api/messages   { recipient_id, subject, body }
    public function store(Request $request)
    {
        $me = $request->user()->id;

        $data = $request->validate([
            'recipient_id' => ['required', 'integer', Rule::notIn([$me]),
                               Rule::exists('users', 'id')->where('is_active', true)],
            'subject'      => ['required', 'string', 'max:150'],
            'body'         => ['required', 'string', 'max:5000'],
        ]);

        $conversation = DB::transaction(function () use ($me, $data) {
            $c = Conversation::create(['subject' => $data['subject'], 'created_by' => $me]);
            $m = $c->messages()->create(['sender_id' => $me, 'body' => $data['body']]);

            ConversationParticipant::create(['conversation_id' => $c->id, 'user_id' => $me, 'last_read_message_id' => $m->id]);
            ConversationParticipant::create(['conversation_id' => $c->id, 'user_id' => $data['recipient_id']]);

            return $c;
        });

        $this->audit($request, 'SEND_MESSAGE', $conversation->id, 'To user #' . $data['recipient_id']);

        return response()->json(['id' => $conversation->id], 201);
    }

    // POST /api/messages/{conversation}/reply   { body }
    public function reply(Request $request, Conversation $conversation)
    {
        $row = $this->participant($request, $conversation);
        $me  = $request->user()->id;

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $message = DB::transaction(function () use ($conversation, $row, $me, $data) {
            $m = $conversation->messages()->create(['sender_id' => $me, 'body' => $data['body']]);
            $row->update(['last_read_message_id' => $m->id]);

            // anyone who had deleted the conversation sees it again when a new reply arrives
            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $me)->update(['deleted_at' => null]);

            $conversation->touch();

            return $m;
        });

        $this->audit($request, 'REPLY_MESSAGE', $conversation->id);

        return response()->json(['id' => $message->id], 201);
    }

    // DELETE /api/messages/{conversation}   (hides it for you only; it can be restored)
    public function destroy(Request $request, Conversation $conversation)
    {
        $this->participant($request, $conversation)->update(['deleted_at' => now()]);
        $this->audit($request, 'DELETE_MESSAGE_THREAD', $conversation->id, 'Hidden for this user only');

        return response()->json(['message' => 'Conversation deleted.']);
    }

    // POST /api/messages/{conversation}/restore
    public function restore(Request $request, Conversation $conversation)
    {
        $this->participant($request, $conversation, true)->update(['deleted_at' => null]);
        $this->audit($request, 'RESTORE_MESSAGE_THREAD', $conversation->id);

        return response()->json(['message' => 'Conversation restored.']);
    }

    // ---------- helpers ----------

    /** Your own membership row. Anyone else gets a 404, so a conversation's existence is not revealed. */
    private function participant(Request $request, Conversation $conversation, bool $allowDeleted = false): ConversationParticipant
    {
        $row = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)->first();

        abort_if(! $row || (! $allowDeleted && $row->deleted_at), 404, 'Conversation not found.');

        return $row;
    }

    /** Conversations that contain a message from someone else that you have not read yet. */
    private function unreadIds(int $me): array
    {
        return DB::table('messages as m')
            ->join('conversation_participants as cp', function ($join) use ($me) {
                $join->on('cp.conversation_id', '=', 'm.conversation_id')->where('cp.user_id', $me);
            })
            ->whereNull('cp.deleted_at')
            ->where('m.sender_id', '!=', $me)
            ->where(function ($q) {
                $q->whereNull('cp.last_read_message_id')->orWhereColumn('m.id', '>', 'cp.last_read_message_id');
            })
            ->distinct()->pluck('m.conversation_id')->all();
    }

    /** Message text is never written to the audit log. */
    private function audit(Request $request, string $action, int $id, ?string $details = null): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'Conversation',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
            'details'     => $details,
        ]);
    }
}