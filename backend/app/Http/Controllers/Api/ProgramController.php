<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Event;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    /** Historical and current program states accepted by the API. */
    private const VALID_STATUSES = ['active', 'completed', 'closed', 'cancelled', 'archived'];

    // GET /api/programs   (people who manage programs also get real child / event counts)
    public function index(Request $request)
    {
        $programs = Program::latest()->get();

        if (! $request->user()->can('manage_programs')) {
            return response()->json($programs);
        }

        $children = Child::where('status', 'active')->whereNotNull('program')
            ->selectRaw('program, COUNT(*) as total')->groupBy('program')->pluck('total', 'program');
        $events = Event::whereNotNull('program_id')
            ->selectRaw('program_id, COUNT(*) as total')->groupBy('program_id')->pluck('total', 'program_id');

        return response()->json($programs->map(fn ($p) => array_merge($p->toArray(), [
            'children_count' => (int) ($children[$p->name] ?? 0),
            'events_count'   => (int) ($events[$p->id] ?? 0),
        ]))->values());
    }

    // GET /api/programs/{program}
    public function show(Program $program)
    {
        return response()->json($program);
    }

    // POST /api/programs
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('programs', 'name')],
            'description' => ['nullable', 'string'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $data['status'] = 'active';

        $program = Program::create($data);
        $this->log($request, 'CREATE_PROGRAM', $program->id);

        return response()->json($program, 201);
    }

    // PUT /api/programs/{program}
    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255', Rule::unique('programs', 'name')->ignore($program->id)],
            'description' => ['nullable', 'string'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'      => ['sometimes', 'in:' . implode(',', self::VALID_STATUSES)],
        ]);

        $oldName = $program->name;

        // Children and officers are linked by program NAME, so a rename must carry the children
        // with it, otherwise field officers would silently lose access to them.
        DB::transaction(function () use ($program, $data, $oldName) {
            $program->update($data);

            if (isset($data['name']) && $data['name'] !== $oldName) {
                Child::where('program', $oldName)->update(['program' => $data['name']]);
            }
        });

        $renamed = isset($data['name']) && $data['name'] !== $oldName;
        $this->log($request, 'UPDATE_PROGRAM', $program->id, $renamed ? "Renamed from \"$oldName\"" : null);

        return response()->json($program->fresh());
    }

    // PATCH /api/programs/{program}/archive
    public function archive(Request $request, Program $program)
    {
        $program->update(['status' => 'archived']);
        $this->log($request, 'ARCHIVE_PROGRAM', $program->id);

        return response()->json(['message' => 'Program archived']);
    }

    private function log(Request $request, string $action, int $id, ?string $details = null): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'Program',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
            'details'     => $details,
        ]);
    }
}