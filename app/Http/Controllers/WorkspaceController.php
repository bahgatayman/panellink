<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Services\AvailabilityService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * The single entry point for /workspaces: Rooms are the primary content,
     * the workspace is just context. One workspace -> its rooms show
     * directly, no picker step. Zero -> the "create your workspace" empty
     * state becomes the whole page. Several -> a lightweight chip selector
     * (?workspace=) picks which one's rooms are shown; an unknown/foreign id
     * degrades to the owner's own first workspace rather than a 404.
     */
    public function index(Request $request, AvailabilityService $availability): View
    {
        $workspaces = Workspace::where('owner_id', TenantContext::id())
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        if ($workspaces->isEmpty()) {
            return view('workspaces.index', [
                'workspaces' => $workspaces,
                'activeWorkspace' => null,
                'rooms' => collect(),
                'roomStats' => null,
            ]);
        }

        // firstWhere() searches within this already owner-scoped collection,
        // never a raw Workspace::find() — a foreign/stale id simply won't
        // match, so another tenant's workspace can never be selected this way.
        $activeWorkspace = $workspaces->count() === 1
            ? $workspaces->first()
            : $workspaces->firstWhere('id', (int) $request->query('workspace')) ?? $workspaces->first();

        [$rooms, $roomStats] = $this->roomsForWorkspace($activeWorkspace, $request, $availability);

        return view('workspaces.index', compact('workspaces', 'activeWorkspace', 'rooms', 'roomStats'));
    }

    /**
     * Room list + stat strip for one workspace, in a fixed 2 queries
     * regardless of room count: one for the rooms themselves (with Custom
     * Plans counts and shared-room live seat sums folded in via
     * withCount/withSum), one for every exclusive room's live occupancy
     * (AvailabilityService::usedCapacityNowBulk — the batched counterpart
     * to usedCapacityNow()). Search is deliberately not handled here — it's
     * 100% client-side (<x-ui.search>), matching every other list page.
     *
     * @return array{0: Collection<int, Room>, 1: array{total: int, available: int, occupied: int, unavailable: int}}
     */
    private function roomsForWorkspace(Workspace $workspace, Request $request, AvailabilityService $availability): array
    {
        $type = $request->query('type');

        $rooms = Room::where('workspace_id', $workspace->id)
            ->where('owner_id', $workspace->owner_id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->withCount(['plans', 'activePricingProfiles as pricing_profiles_count'])
            ->withSum(['sharedSessions as occupied_seats' => fn ($q) => $q->where('status', 'open')], 'party_size')
            ->orderBy('name')
            ->get();

        $exclusiveIds = $rooms->reject(fn (Room $r) => $r->isShared())->pluck('id')->all();
        $exclusiveUsage = $availability->usedCapacityNowBulk($exclusiveIds, $workspace->owner_id);

        $rooms->each(function (Room $room) use ($exclusiveUsage) {
            $room->occupied_seats = $room->isShared()
                ? (int) ($room->occupied_seats ?? 0)
                : (int) ($exclusiveUsage[$room->id] ?? 0);
        });

        $roomStats = [
            'total' => $rooms->count(),
            'available' => $rooms->filter(fn (Room $r) => $r->statusKey($r->occupied_seats) === 'available')->count(),
            'occupied' => $rooms->filter(fn (Room $r) => $r->statusKey($r->occupied_seats) === 'occupied')->count(),
            'unavailable' => $rooms->filter(fn (Room $r) => $r->statusKey($r->occupied_seats) === 'unavailable')->count(),
        ];

        return [$rooms, $roomStats];
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

        return redirect()->route('workspaces.index', ['workspace' => $workspace->id])
            ->with('success', 'Workspace created successfully.');
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

        return redirect()->route('workspaces.index', ['workspace' => $workspace->id])
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
