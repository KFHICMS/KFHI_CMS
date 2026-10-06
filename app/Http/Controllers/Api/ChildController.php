<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    // GET /api/children
    public function index(Request $request)
    {
        $children = Child::where('status', 'active')->latest()->get();
        $user = $request->user();

        return response()->json(
            $children->map(fn ($c) => $this->transform($c->load('guardians'), $user))
        );
    }

    // POST /api/children
    public function store(Request $request)
    {
        $data = $this->validateChild($request);

        $data['child_code'] = 'KFHI-' . date('Y') . '-' .
            str_pad((int) Child::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $data['status'] = 'active';

        $guardians = $data['guardians'] ?? [];
        unset($data['guardians']);

        $child = Child::create($data);
        foreach ($guardians as $g) {
            $child->guardians()->create($g);
        }

        $this->log($request, 'CREATE_CHILD', $child->id);

        return response()->json($this->transform($child->load('guardians'), $request->user()), 201);
    }

    // GET /api/children/{child}
    public function show(Request $request, Child $child)
    {
        $this->log($request, 'VIEW_CHILD', $child->id);
        return response()->json($this->transform($child->load('guardians'), $request->user()));
    }

    // PUT /api/children/{child}
    public function update(Request $request, Child $child)
    {
        $data = $this->validateChild($request);
        unset($data['guardians']); // guardians handled separately for simplicity
        $child->update($data);

        $this->log($request, 'UPDATE_CHILD', $child->id);

        return response()->json($this->transform($child->fresh()->load('guardians'), $request->user()));
    }

    // PATCH /api/children/{child}/archive
    public function archive(Request $request, Child $child)
    {
        $child->update(['status' => 'archived']);
        $this->log($request, 'ARCHIVE_CHILD', $child->id);
        return response()->json(['message' => 'Child archived']);
    }

    // ---- helpers ----

    private function validateChild(Request $request): array
    {
        return $request->validate([
            'full_name'             => ['required', 'string', 'max:255'],
            'date_of_birth'         => ['nullable', 'date'],
            'gender'                => ['nullable', 'string', 'max:20'],
            'address'               => ['nullable', 'string'],
            'school'                => ['nullable', 'string'],
            'grade'                 => ['nullable', 'string'],
            'education_status'      => ['nullable', 'string'],
            'medical_info'          => ['nullable', 'string'],
            'special_requirements'  => ['nullable', 'string'],
            'emergency_contacts'    => ['nullable', 'string'],
            'program'               => ['nullable', 'string'],
            'registration_date'     => ['nullable', 'date'],
            'participation_details' => ['nullable', 'string'],
            'guardians'                     => ['nullable', 'array'],
            'guardians.*.name'              => ['required_with:guardians', 'string'],
            'guardians.*.relationship'      => ['nullable', 'string'],
            'guardians.*.contact_number'    => ['nullable', 'string'],
            'guardians.*.address'           => ['nullable', 'string'],
        ]);
    }

    /**
     * THE KEY SECURITY STEP: return only the fields this user's role may see.
     */
   private function transform(Child $child, $user): array
{
    return $child->visibleTo($user);
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
