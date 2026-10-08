<?php

namespace App\Http\Controllers\ChildOfficer;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        $query = Child::query();
        
        if ($search = $request->input('search')) {
            $query->where('name_english', 'like', "%{$search}%")
                  ->orWhere('child_code', 'like', "%{$search}%");
        }
        
        $children = $query->paginate(15);
        return view('child_officer.children.index', compact('children'));
    }

    public function create()
    {
        return view('child_officer.children.create');
    }

    public function store(Request $request)
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

        Child::create($validated);
        
        return redirect()->route('child-officer.children.index')->with('success', 'Child registered successfully.');
    }

    public function show(Child $child)
    {
        // Generate QR code using external API since extension gd is missing
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($child->child_code);
        
        return view('child_officer.children.show', compact('child', 'qrCodeUrl'));
    }

    public function edit(Child $child)
    {
        return view('child_officer.children.edit', compact('child'));
    }

    public function update(Request $request, Child $child)
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

        $child->update($validated);
        
        return redirect()->route('child-officer.children.index')->with('success', 'Child updated successfully.');
    }

    public function destroy(Child $child)
    {
        $child->delete();
        return redirect()->route('child-officer.children.index')->with('success', 'Child deleted successfully.');
    }
}
