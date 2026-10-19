<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\ChildCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChildCategoryController extends Controller
{
    // GET /api/children/{child}/categories
    public function show(Request $request, Child $child)
    {
        abort_unless($child->isAccessibleTo($request->user()), 403, 'This child is outside your assigned programs.');

        return response()->json([
            'options'    => ChildCategory::OPTIONS,
            'categories' => ChildCategory::where('child_id', $child->id)->orderBy('category')->pluck('category'),
        ]);
    }

    // PUT /api/children/{child}/categories   { "categories": ["education","health"] }
    public function update(Request $request, Child $child)
    {
        abort_unless($child->isAccessibleTo($request->user()), 403, 'This child is outside your assigned programs.');

        $data = $request->validate([
            'categories'   => ['present', 'array'],
            'categories.*' => ['string', Rule::in(ChildCategory::OPTIONS)],
        ]);

        $wanted = array_values(array_unique($data['categories']));

        DB::transaction(function () use ($child, $wanted) {
            ChildCategory::where('child_id', $child->id)->whereNotIn('category', $wanted)->delete();
            foreach ($wanted as $category) {
                ChildCategory::firstOrCreate(['child_id' => $child->id, 'category' => $category]);
            }
        });

        AuditLog::create([
            'user_id'     => $request->user()->id,
            'action'      => 'UPDATE_CHILD_CATEGORIES',
            'entity_type' => 'Child',
            'entity_id'   => $child->id,
            'ip_address'  => $request->ip(),
            'details'     => $wanted ? implode(',', $wanted) : 'none',
        ]);

        return response()->json(['categories' => $wanted]);
    }
}