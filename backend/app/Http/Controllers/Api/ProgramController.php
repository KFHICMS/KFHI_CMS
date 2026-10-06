<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    // GET /api/programs
    public function index()
    {
        return response()->json(Program::latest()->get());
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
            'name'        => ['required', 'string', 'max:255'],
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
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'      => ['sometimes', 'in:active,completed,archived'],
        ]);

        $program->update($data);
        $this->log($request, 'UPDATE_PROGRAM', $program->id);

        return response()->json($program);
    }

    // PATCH /api/programs/{program}/archive
    public function archive(Request $request, Program $program)
    {
        $program->update(['status' => 'archived']);
        $this->log($request, 'ARCHIVE_PROGRAM', $program->id);
        return response()->json(['message' => 'Program archived']);
    }

    private function log(Request $request, string $action, int $id): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'Program',
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
        ]);
    }
}
