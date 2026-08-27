<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(): View
    {
        $workspaces = Workspace::where('owner_id', TenantContext::id())
            ->withCount('rooms')
            ->latest()
            ->paginate(10);

        return view('workspaces.index', compact('workspaces'));
    }

    public function create(): View
    {
        return view('workspaces.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! TenantContext::user()->canAddMoreWorkspaces()) {
            return back()->withInput()->with('error', __('app.plan_limit.workspaces'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        $workspace = Workspace::create(array_merge($data, [
            'owner_id' => TenantContext::id(),
        ]));

        $this->activityLogger->log('workspace.created', $workspace, "Created workspace {$workspace->name}");

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace created successfully.');
    }

    public function show(int $id): View
    {
        $workspace = Workspace::where('id', $id)
            ->where('owner_id', TenantContext::id())
            ->with('rooms')
            ->firstOrFail();

        $roomsByType = $workspace->rooms->groupBy('type');

        return view('workspaces.show', compact('workspace', 'roomsByType'));
    }

    public function edit(int $id): View
    {
        $workspace = Workspace::where('id', $id)
            ->where('owner_id', TenantContext::id())
            ->firstOrFail();

        return view('workspaces.edit', compact('workspace'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $workspace = Workspace::where('id', $id)
            ->where('owner_id', TenantContext::id())
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        $workspace->update($data);

        $this->activityLogger->log('workspace.updated', $workspace, "Updated workspace {$workspace->name}");

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Workspace updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $workspace = Workspace::where('id', $id)
            ->where('owner_id', TenantContext::id())
            ->firstOrFail();

        if ($workspace->rooms()->count() > 0) {
            return back()->with('error', 'Cannot delete workspace with rooms. Delete all rooms first.');
        }

        $this->activityLogger->log('workspace.deleted', $workspace, "Deleted workspace {$workspace->name}");

        $workspace->delete();

        return redirect()->route('workspaces.index')
            ->with('success', 'Workspace deleted successfully.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $workspace = Workspace::where('id', $id)
            ->where('owner_id', TenantContext::id())
            ->firstOrFail();

        $workspace->update(['is_active' => ! $workspace->is_active]);

        $this->activityLogger->log('workspace.toggled', $workspace, "Workspace '{$workspace->name}' ".($workspace->is_active ? 'activated' : 'deactivated'));

        return back()->with(
            'success',
            "Workspace '{$workspace->name}' ".($workspace->is_active ? 'activated' : 'deactivated').'.'
        );
    }
}
