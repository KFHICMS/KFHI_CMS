<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Child;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    // GET /api/follow-ups?status=pending&assigned_to=5
    public function index(Request $request)
    {
        $q = FollowUp::with(['child:id,child_code,full_name', 'assignee:id,name']);
        if ($request->filled('status'))      $q->where('status', $request->status);
        if ($request->filled('assigned_to')) $q->where('assigned_to', $request->assigned_to);
        return response()->json($q->latest()->get());
    }

    // POST /api/children/{child}/follow-ups
    public function store(Request $request, Child $child)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
            'due_date'    => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);
        $data['child_id']   = $child->id;
        $data['status']     = 'pending';
        $data['created_by'] = $request->user()->id;

        $followUp = FollowUp::create($data);
        $this->log($request, 'CREATE_FOLLOWUP', $child->id);

        return response()->json($followUp->load('assignee:id,name'), 201);
    }

    // GET /api/children/{child}/follow-ups
    public function childHistory(Child $child)
    {
        return response()->json(
            $child->followUps()->with('assignee:id,name')->latest()->get()
        );
    }

    // PATCH /api/follow-ups/{followUp}/complete
    public function complete(Request $request, FollowUp $followUp)
    {
        $followUp->update(['status' => 'completed', 'completed_at' => now()]);
        $this->log($request, 'COMPLETE_FOLLOWUP', $followUp->child_id);
        return response()->json(['message' => 'Follow-up completed', 'follow_up' => $followUp]);
    }

    // PUT /api/follow-ups/{followUp}  (update / reassign)
    public function update(Request $request, FollowUp $followUp)
    {
        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'notes'       => ['nullable', 'string'],
            'due_date'    => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status'      => ['sometimes', 'in:pending,completed'],
        ]);
        if (($data['status'] ?? null) === 'completed' && $followUp->status !== 'completed') {
            $data['completed_at'] = now();
        }
        $followUp->update($data);
        $this->log($request, 'UPDATE_FOLLOWUP', $followUp->child_id);
        return response()->json($followUp->fresh()->load('assignee:id,name'));
    }

    private function log(Request $request, string $action, int $childId): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => 'Child',
            'entity_id'   => $childId,
            'ip_address'  => $request->ip(),
        ]);
    }
}
