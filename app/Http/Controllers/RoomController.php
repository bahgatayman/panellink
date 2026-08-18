<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Workspace;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    private function getWorkspace(int $workspaceId): Workspace
    {
        return Workspace::where('id', $workspaceId)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();
    }

    private function getRoom(Workspace $workspace, int $roomId): Room
    {
        return Room::where('id', $roomId)
            ->where('workspace_id', $workspace->id)
            ->firstOrFail();
    }

    public function create(int $workspaceId): View
    {
        $workspace = $this->getWorkspace($workspaceId);

        $roomTypes = [
            'meeting' => 'Meeting Room',
            'training' => 'Training Room',
            'shared' => 'Shared Space',
            'office' => 'Private Office',
        ];

        return view('workspaces.rooms.create', compact('workspace', 'roomTypes'));
    }

    public function store(Request $request, int $workspaceId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);

        if (! auth('owner')->user()->canAddMoreRooms()) {
            return back()->withInput()->with('error', __('app.plan_limit.rooms'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:meeting,training,shared,office',
            'capacity' => 'required|integer|min:1|max:999',
            'price_per_hour' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        Room::create(array_merge($data, [
            'workspace_id' => $workspace->id,
            'owner_id' => auth('owner')->id(),
        ]));

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room added successfully.');
    }

    public function edit(int $workspaceId, int $roomId): View
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $roomTypes = [
            'meeting' => 'Meeting Room',
            'training' => 'Training Room',
            'shared' => 'Shared Space',
            'office' => 'Private Office',
        ];

        return view('workspaces.rooms.edit', compact('workspace', 'room', 'roomTypes'));
    }

    public function update(Request $request, int $workspaceId, int $roomId, AvailabilityService $availability): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:meeting,training,shared,office',
            'capacity' => 'required|integer|min:1|max:999',
            'price_per_hour' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        // A type flip mid-occupancy is a bigger semantic break than a
        // capacity number changing, so it's blocked outright rather than
        // measured against usage: there is no "how many open sessions is
        // too many" threshold, any open session on this room makes the
        // room type itself still live.
        if ($data['type'] !== $room->type && $room->openSharedSessions()->exists()) {
            return back()->withInput()->with('error', __('app.workspace.type_change_blocked_open_session'));
        }

        // effectiveCapacity() under the *incoming* type/capacity — a
        // shared→exclusive flip pins this to 1 regardless of the submitted
        // capacity value, which doubles as protection against converting a
        // room away from shared while it still has future bookings for
        // more than one person (the type-change guard above only covers
        // currently-open sessions, not that case).
        $newEffectiveCapacity = $data['type'] === 'shared' ? $data['capacity'] : 1;
        $committedUsage = $availability->peakCommittedUsage($room);

        if ($newEffectiveCapacity < $committedUsage) {
            return back()->withInput()->with('error',
                __('app.workspace.capacity_below_committed_usage', ['count' => $committedUsage]));
        }

        $room->update($data);

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $room->delete();

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room deleted successfully.');
    }

    public function toggleAvailable(int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $room->update(['is_available' => ! $room->is_available]);

        return back()->with(
            'success',
            "Room '{$room->name}' marked as ".($room->is_available ? 'available' : 'unavailable').'.'
        );
    }
}
