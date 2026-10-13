<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ChildController extends Controller
{
    // GET /api/children?q=&program=&area=&office_code=&gender=&service_state=&sponsor_state=&status=&page=&per_page=
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Child::accessibleTo($user)->with('guardians');

        $status = $request->input('status', 'active');
        if ($status !== 'all') {
            $query->where('status', $status === 'archived' ? 'archived' : 'active');
        }

        if ($request->filled('q')) {
            $term = '%' . addcslashes($request->input('q'), '%_\\') . '%';
            $query->where(function ($w) use ($term) {
                $w->where('full_name', 'like', $term)
                  ->orWhere('child_code', 'like', $term)
                  ->orWhere('name_korean', 'like', $term)
                  ->orWhere('alias', 'like', $term);
            });
        }

        // exact-match filters (all non-sensitive, all indexed or low-cardinality)
        foreach (['program', 'area', 'office_code', 'gender', 'service_state', 'sponsor_state'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page    = $query->latest('id')->paginate($perPage);

        return response()->json(
            $page->through(fn ($c) => $c->visibleTo($user))
        );
    }

    // POST /api/children
    public function store(Request $request)
    {
        $data = $this->validateChild($request, creating: true);

        // Child Code: use the one entered by staff (e.g. LKA005001); generate one only if left empty.
        if (empty($data['child_code'])) {
            $data['child_code'] = 'KFHI-' . date('Y') . '-' .
                str_pad((int) Child::max('id') + 1, 4, '0', STR_PAD_LEFT);
        }
        $data['status'] = 'active';

        $guardians = $data['guardians'] ?? [];
        unset($data['guardians']);

        $child = Child::create($data);
        foreach ($guardians as $g) {
            $child->guardians()->create($g);
        }

        $this->log($request, 'CREATE_CHILD', $child->id);

        return response()->json($child->load('guardians')->visibleTo($request->user()), 201);
    }

    // GET /api/children/{child}
    public function show(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);
        $this->log($request, 'VIEW_CHILD', $child->id);

        return response()->json($child->load('guardians')->visibleTo($request->user()));
    }

    // PUT /api/children/{child}   (the Child Code can't be changed: QR codes and ID cards depend on it)
    public function update(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);

        $data = $this->validateChild($request);
        $guardians = $data['guardians'] ?? null;
        unset($data['guardians']);

        $child->update($data);

        // If a guardians array is sent, it replaces the existing list.
        if (is_array($guardians)) {
            $child->guardians()->delete();
            foreach ($guardians as $g) {
                $child->guardians()->create($g);
            }
        }

        $this->log($request, 'UPDATE_CHILD', $child->id);

        return response()->json($child->fresh()->load('guardians')->visibleTo($request->user()));
    }

    // PATCH /api/children/{child}/archive
    public function archive(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);

        $child->update(['status' => 'archived']);
        // An archived child's QR codes stop working immediately.
        \App\Models\QrToken::where('child_id', $child->id)->update(['is_active' => false]);

        $this->log($request, 'ARCHIVE_CHILD', $child->id);
        return response()->json(['message' => 'Child archived']);
    }

    // POST /api/children/{child}/photo   (multipart: photo)
    public function uploadPhoto(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($child->photo_path) {
            Storage::disk('local')->delete($child->photo_path);
        }

        // Private disk: photos are NOT web-accessible, only via the endpoint below.
        $path = $request->file('photo')->store('child-photos', 'local');
        $child->update(['photo_path' => $path]);

        $this->log($request, 'UPLOAD_CHILD_PHOTO', $child->id);

        return response()->json(['has_photo' => true]);
    }

    // GET /api/children/{child}/photo
    public function photo(Request $request, Child $child)
    {
        $this->authorizeChild($request, $child);

        abort_unless(
            $child->photo_path && Storage::disk('local')->exists($child->photo_path),
            404,
            'No photo'
        );

        return Storage::disk('local')->response($child->photo_path);
    }

    // ---- helpers ----

    private function authorizeChild(Request $request, Child $child): void
    {
        abort_unless(
            $child->isAccessibleTo($request->user()),
            403,
            'This child is outside your assigned programs.'
        );
    }

    private function validateChild(Request $request, bool $creating = false): array
    {
        $rules = [
            'full_name'             => ['required', 'string', 'max:255'],
            'name_korean'           => ['nullable', 'string', 'max:255'],
            'alias'                 => ['nullable', 'string', 'max:255'],
            'date_of_birth'         => ['nullable', 'date', 'before_or_equal:today'],
            'gender'                => ['nullable', 'string', 'max:20'],
            'religion'              => ['nullable', 'string', 'max:100'],
            'address'               => ['nullable', 'string'],
            'area'                  => ['nullable', 'string', 'max:150'],
            'gnd'                   => ['nullable', 'string', 'max:150'],
            'office_code'           => ['nullable', 'string', 'max:50'],
            'office_name'           => ['nullable', 'string', 'max:150'],
            'service_state'         => ['nullable', 'string', 'max:100'],
            'sponsor_state'         => ['nullable', 'string', 'max:100'],
            'guardian_type'         => ['nullable', 'string', 'max:100'],
            'caregiver'             => ['nullable', 'string', 'max:255'],
            'school'                => ['nullable', 'string'],
            'curriculum'            => ['nullable', 'string', 'max:150'],
            'grade'                 => ['nullable', 'string'],
            'education_status'      => ['nullable', 'string'],
            'favorite_subject'      => ['nullable', 'string', 'max:150'],
            'pass_fail'             => ['nullable', Rule::in(['Pass', 'Fail'])],
            'dream'                 => ['nullable', 'string', 'max:255'],
            'dream_description'     => ['nullable', 'string'],
            'favorite_activity'     => ['nullable', 'string', 'max:255'],
            'health'                => ['nullable', 'string'],
            'medical_info'          => ['nullable', 'string'],     // shown as "Health description"
            'special_requirements'  => ['nullable', 'string'],
            'disability_type'       => ['nullable', 'string', 'max:255'],
            'disability_description'=> ['nullable', 'string'],
            'emergency_contacts'    => ['nullable', 'string'],
            'program'               => ['nullable', 'string'],
            'registration_date'     => ['nullable', 'date'],
            'participation_details' => ['nullable', 'string'],
            'guardians'                     => ['nullable', 'array'],
            'guardians.*.name'              => ['required_with:guardians', 'string'],
            'guardians.*.relationship'      => ['nullable', 'string'],
            'guardians.*.contact_number'    => ['nullable', 'string'],
            'guardians.*.address'           => ['nullable', 'string'],
        ];

        if ($creating) {
            // optional on create; must be unique when entered
            $rules['child_code'] = ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:children,child_code'];
        }

        return $request->validate($rules);
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