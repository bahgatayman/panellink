<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('owners')
            ->orderBy('sort_order')
            ->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        $features = Feature::where('is_active', true)->orderBy('id')->get();

        return view('admin.plans.create', compact('features'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        Plan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function edit($id)
    {
        $plan     = Plan::findOrFail($id);
        $features = Feature::where('is_active', true)->orderBy('id')->get();

        return view('admin.plans.edit', compact('plan', 'features'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validatePlan($request, $id);

        Plan::findOrFail($id)->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated successfully.');
    }

    /** Shared validation + normalisation for create/update. */
    private function validatePlan(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'slug'            => 'required|string|unique:plans,slug' . ($id ? ",{$id}" : ''),
            'max_members'     => 'required|integer|min:1',
            'max_workspaces'  => 'required|integer|min:0',
            'max_rooms'       => 'required|integer|min:0',
            'max_products'    => 'required|integer|min:0',
            'price_per_month' => 'required|numeric|min:0',
            'sort_order'      => 'integer|min:0',
            'features'        => 'nullable|array',
            'features.*'      => 'string|exists:features,key',
        ]);

        // Always persist the feature set (empty when nothing is checked).
        $validated['features'] = $request->input('features', []);

        return $validated;
    }

    public function toggle($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Plan {$status} successfully.");
    }
}
