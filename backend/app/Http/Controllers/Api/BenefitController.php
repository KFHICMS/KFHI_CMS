<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\BenefitType;
use App\Models\Child;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    // GET /api/benefit-types
    public function types()
    {
        return response()->json(BenefitType::orderBy('name')->get());
    }

    // POST /api/benefit-types  (admin defines a gift type)
    public function storeType(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:benefit_types,name'],
            'description' => ['nullable', 'string'],
        ]);
        $type = BenefitType::create($data);
        $this->log($request, 'CREATE_BENEFIT_TYPE', 'BenefitType', $type->id);
        return response()->json($type, 201);
    }

    // POST /api/benefits  (record a benefit given to a child)
    public function store(Request $request)
    {
        $data = $request->validate([
            'child_id'        => ['required', 'exists:children,id'],
            'benefit_type_id' => ['required', 'exists:benefit_types,id'],
            'event_id'        => ['nullable', 'exists:events,id'],
            'quantity'        => ['nullable', 'integer', 'min:1'],
            'notes'           => ['nullable', 'string'],
        ]);
        $data['quantity'] = $data['quantity'] ?? 1;
        $data['given_by'] = $request->user()->id;

        $benefit = Benefit::create($data);
        $this->log($request, 'RECORD_BENEFIT', 'Child', $data['child_id']);

        return response()->json(
            $benefit->load(['type', 'child:id,child_code,full_name']),
            201
        );
    }

    // GET /api/children/{child}/benefits  (a child's benefit history)
    public function childHistory(Child $child)
    {
        return response()->json(
            $child->benefits()->with('type')->latest('given_at')->get()
        );
    }

    private function log(Request $request, string $action, string $entity, int $id): void
    {
        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'entity_type' => $entity,
            'entity_id'   => $id,
            'ip_address'  => $request->ip(),
        ]);
    }
}
