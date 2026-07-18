<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'slug'            => 'required|string|unique:plans,slug',
            'max_members'     => 'required|integer|min:1',
            'price_per_month' => 'required|numeric|min:0',
            'sort_order'      => 'integer|min:0',
        ]);

        Plan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'slug'            => 'required|string|unique:plans,slug,' . $id,
            'max_members'     => 'required|integer|min:1',
            'price_per_month' => 'required|numeric|min:0',
            'sort_order'      => 'integer|min:0',
        ]);

        Plan::findOrFail($id)->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated successfully.');
    }

    public function toggle($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Plan {$status} successfully.");
    }
}
