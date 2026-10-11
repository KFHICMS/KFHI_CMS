<?php

namespace App\Http\Controllers\ChildOfficer;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Child::query();

        $childCode = $request->query('child_code');
        if (is_string($childCode) && trim($childCode) !== '') {
            $query->where('child_code', trim($childCode));
        } else {
            $search = $request->input('search');
            if (is_string($search) && trim($search) !== '') {
                $search = trim($search);
                $query->where(function (Builder $childQuery) use ($search): void {
                    $childQuery->where('name_english', 'like', "%{$search}%")
                        ->orWhere('name_korean', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('child_code', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%")
                        ->orWhere('office_name', 'like', "%{$search}%");
                });
            }
        }

        $gender = $request->input('gender');
        if (is_string($gender) && $gender !== '') {
            $query->where('gender', $gender);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $children = $query->latest()->get([
                'id',
                'child_code',
                'full_name',
                'name_english',
                'name_korean',
                'alias',
                'date_of_birth',
                'school',
                'gender',
                'religion',
                'area',
                'office_code',
                'office_name',
                'service_state',
                'sponsor_state',
                'guardian_type',
                'caregiver',
                'curriculum',
                'grade',
                'favorite_subject',
                'pass_fail',
                'dream',
                'dream_description',
                'favorite_activity',
                'disability_type',
                'disability_description',
            ]);

            return response()->json($children->map(fn (Child $child): array => [
                'id' => $child->id,
                'child_code' => $child->child_code,
                'full_name' => $child->full_name,
                'name_english' => $child->name_english ?: $child->full_name,
                'name_korean' => $child->name_korean,
                'alias' => $child->alias,
                'date_of_birth' => $child->date_of_birth,
                'age' => $child->age,
                'school' => $child->school,
                'gender' => $child->gender,
                'religion' => $child->religion,
                'area' => $child->area,
                'office_code' => $child->office_code,
                'office_name' => $child->office_name,
                'service_state' => $child->service_state,
                'sponsor_state' => $child->sponsor_state,
                'guardian_type' => $child->guardian_type,
                'caregiver' => $child->caregiver,
                'curriculum' => $child->curriculum,
                'grade' => $child->grade,
                'favorite_subject' => $child->favorite_subject,
                'pass_fail' => $child->pass_fail,
                'dream' => $child->dream,
                'dream_description' => $child->dream_description,
                'favorite_activity' => $child->favorite_activity,
                'disability_type' => $child->disability_type,
                'disability_description' => $child->disability_description,
            ]));
        }

        $children = $query->latest()->paginate(15);

        return view('child_officer.children.index', compact('children'));
    }

    public function create(): View
    {
        return view('child_officer.children.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'child_code' => 'required|unique:children,child_code',
            'name_english' => 'required|string|max:255',
            'name_korean' => 'nullable|string|max:255',
            'alias' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'religion' => 'nullable|string',
            'area' => 'nullable|string',
            'office_code' => 'nullable|string',
            'office_name' => 'nullable|string',
            'service_state' => 'nullable|string',
            'sponsor_state' => 'nullable|string',
            'guardian_type' => 'nullable|string',
            'caregiver' => 'nullable|string',
            'curriculum' => 'nullable|string',
            'grade' => 'nullable|string',
            'favorite_subject' => 'nullable|string',
            'pass_fail' => 'nullable|string',
            'dream' => 'nullable|string',
            'dream_description' => 'nullable|string',
            'favorite_activity' => 'nullable|string',
            'health' => 'nullable|string',
            'health_description' => 'nullable|string',
            'disability_type' => 'nullable|string',
            'disability_description' => 'nullable|string',
        ]);

        $validated['full_name'] = $validated['name_english'];

        Child::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true], 201);
        }

        return redirect()->route('child-officer.children.index')->with('success', 'Child registered successfully.');
    }

    public function show(Child $child): View
    {
        // Generate QR code using external API since extension gd is missing
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.urlencode($child->child_code);

        return view('child_officer.children.show', compact('child', 'qrCodeUrl'));
    }

    public function edit(Child $child): View
    {
        return view('child_officer.children.edit', compact('child'));
    }

    public function update(Request $request, Child $child): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'child_code' => 'required|unique:children,child_code,'.$child->id,
            'name_english' => 'required|string|max:255',
            'name_korean' => 'nullable|string|max:255',
            'alias' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string',
            'religion' => 'nullable|string',
            'area' => 'nullable|string',
            'office_code' => 'nullable|string',
            'office_name' => 'nullable|string',
            'service_state' => 'nullable|string',
            'sponsor_state' => 'nullable|string',
            'guardian_type' => 'nullable|string',
            'caregiver' => 'nullable|string',
            'curriculum' => 'nullable|string',
            'grade' => 'nullable|string',
            'favorite_subject' => 'nullable|string',
            'pass_fail' => 'nullable|string',
            'dream' => 'nullable|string',
            'dream_description' => 'nullable|string',
            'favorite_activity' => 'nullable|string',
            'health' => 'nullable|string',
            'health_description' => 'nullable|string',
            'disability_type' => 'nullable|string',
            'disability_description' => 'nullable|string',
        ]);

        $validated['full_name'] = $validated['name_english'];
        $child->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('child-officer.children.index')->with('success', 'Child updated successfully.');
    }

    public function destroy(Request $request, Child $child): JsonResponse|RedirectResponse
    {
        $child->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('child-officer.children.index')->with('success', 'Child deleted successfully.');
    }
}
