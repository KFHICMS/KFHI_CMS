<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::latest()->get());
    }

    public function show(Event $event)
    {
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'event_date'            => ['nullable', 'date'],
            'qr_attendance_enabled' => ['boolean'],
            'has_questionnaire'     => ['boolean'],
        ]);
        $data['status'] = 'active';

        $event = Event::create($data);
        $this->log($request, 'CREATE_EVENT', $event->id);

        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name'                  => ['sometimes', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'event_date'            => ['nullable', 'date'],
            'qr_attendance_enabled' => ['boolean'],
            'has_questionnaire'     => ['boolean'],
            'status'                => ['sometimes', 'in:active,completed,archived'],
        ]);

        $event->update($data);
        $this->log($request, 'UPDATE_EVENT', $event->id);
        return response()->json($event);
    }

    private function log(Request $request, string $action, int $id): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'Event',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
        ]);
    }
}
