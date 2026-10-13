<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /** Roles that may be assigned to an event. */
    private const ASSIGNABLE_ROLES = ['child_officer', 'field_officer', 'survey_enumerator'];

    // GET /api/events
         // GET /api/events   (enumerators only see the events they are assigned to)
    public function index(Request $request)
    {
        $user = $request->user();
        $with = $user->can('manage_programs') ? ['program:id,name', 'users:id'] : ['program:id,name'];

        return response()->json(
            Event::accessibleTo($user)->with($with)->latest()->get()->map(fn ($e) => $this->present($e, $user))
        );
    }

    // GET /api/events/{event}
    public function show(Request $request, Event $event)
    {
        abort_unless($event->isAccessibleTo($request->user()), 403, 'You are not assigned to this event.');

        return response()->json($this->present($this->loaded($event, $request->user()), $request->user()));
    }

    // POST /api/events
    public function store(Request $request)
    {
        $this->guardOptions($request);

        $data    = $request->validate($this->rules());
        $userIds = $this->validAssignees($data['user_ids'] ?? null);
        unset($data['user_ids']);

        $this->assertDatesOrdered($data['start_at'] ?? null, $data['end_at'] ?? null);

        $data['status'] = 'active';
        // Attendance and questionnaire stay OFF until an administrator switches them on.
        $data['qr_attendance_enabled'] = $data['qr_attendance_enabled'] ?? false;
        $data['has_questionnaire']     = $data['has_questionnaire'] ?? false;

        $event = DB::transaction(function () use ($data, $userIds) {
            $event = Event::create($data);
            if ($userIds !== null) {
                $event->users()->sync($userIds);
            }
            return $event;
        });

        $this->log($request, 'CREATE_EVENT', $event->id);

        return response()->json($this->present($this->loaded($event, $request->user()), $request->user()), 201);
    }

    // PUT /api/events/{event}
    public function update(Request $request, Event $event)
    {
        $this->guardOptions($request);

        $data    = $request->validate($this->rules(updating: true));
        $userIds = $this->validAssignees($data['user_ids'] ?? null);
        unset($data['user_ids']);

        $start = array_key_exists('start_at', $data) ? $data['start_at'] : $event->start_at;
        $end   = array_key_exists('end_at', $data) ? $data['end_at'] : $event->end_at;
        $this->assertDatesOrdered($start, $end);

        $optionKeys = ['qr_attendance_enabled', 'has_questionnaire'];
        $before     = $event->only($optionKeys);

        DB::transaction(function () use ($event, $data, $userIds) {
            $event->update($data);
            if ($userIds !== null) {
                $event->users()->sync($userIds);
            }
        });

        $this->log($request, 'UPDATE_EVENT', $event->id);

        $after = $event->only($optionKeys);
        if ($before !== $after) {
            // who switched attendance / questionnaires on or off, and when, is always traceable
            $this->log($request, 'UPDATE_EVENT_OPTIONS', $event->id, json_encode(['before' => $before, 'after' => $after]));
        }

        return response()->json($this->present($this->loaded($event, $request->user()), $request->user()));
    }

    // ---- helpers ----

    /** Only an administrator may enable/disable attendance and questionnaires (also enforced in the API). */
    private function guardOptions(Request $request): void
    {
        if ($request->hasAny(['qr_attendance_enabled', 'has_questionnaire'])
            && ! $request->user()->can('manage_event_options')) {
            abort(403, 'Only an administrator can enable or disable attendance and questionnaires.');
        }
    }

    private function rules(bool $updating = false): array
    {
        $rules = [
            'name'                  => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'event_date'            => ['nullable', 'date'],
            'program_id'            => ['nullable', 'integer', 'exists:programs,id'],
            'start_at'              => ['nullable', 'date'],
            'end_at'                => ['nullable', 'date'],
            'location'              => ['nullable', 'string', 'max:255'],
            'qr_attendance_enabled' => ['boolean'],
            'has_questionnaire'     => ['boolean'],
            'user_ids'              => ['sometimes', 'array'],
            'user_ids.*'            => ['integer'],
        ];

        if ($updating) {
            $rules['status'] = ['sometimes', 'in:active,completed,archived'];
        }

        return $rules;
    }

    private function assertDatesOrdered($start, $end): void
    {
        if ($start && $end && Carbon::parse($end)->lt(Carbon::parse($start))) {
            throw ValidationException::withMessages([
                'end_at' => 'The end date/time must be on or after the start date/time.',
            ]);
        }
    }

    /** Returns the cleaned id list, or null when no assignment was sent. */
    private function validAssignees(?array $ids): ?array
    {
        if ($ids === null) {
            return null;
        }

        $ids   = array_values(array_unique($ids));
        $valid = User::whereIn('id', $ids)->where('is_active', true)
            ->role(self::ASSIGNABLE_ROLES)->pluck('id')->all();

        if (count($valid) !== count($ids)) {
            throw ValidationException::withMessages([
                'user_ids' => 'Only active officers or enumerators can be assigned to an event.',
            ]);
        }

        return $valid;
    }

    private function loaded(Event $event, $user): Event
    {
        return $event->load($user->can('manage_programs') ? ['program:id,name', 'users:id'] : ['program:id,name']);
    }

    /** Assigned-user ids are only shown to people who manage events. */
    private function present(Event $event, $user): array
    {
        $data = Arr::except($event->toArray(), ['users']);

        if ($user->can('manage_programs')) {
            $data['user_ids'] = $event->users->pluck('id')->values();
        }

        return $data;
    }

    private function log(Request $request, string $action, int $id, ?string $details = null): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'Event',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
            'details'     => $details,
        ]);
    }
}