<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Services\AvailabilityService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    private function getWorkspace(int $workspaceId): Workspace
    {
        return Workspace::where('id', $workspaceId)
            ->where('owner_id', TenantContext::id())
            ->firstOrFail();
    }

    private function getRoom(Workspace $workspace, int $roomId): Room
    {
        return Room::where('id', $roomId)
            ->where('workspace_id', $workspace->id)
            ->firstOrFail();
    }

    /** Keyed the same as the `type` column's allowed values, sourced from lang so the dropdown is bilingual. */
    private function roomTypeOptions(): array
    {
        return Lang::get('app.room_type');
    }

    public function create(int $workspaceId): View
    {
        $workspace = $this->getWorkspace($workspaceId);

        $roomTypes = $this->roomTypeOptions();

        return view('workspaces.rooms.create', compact('workspace', 'roomTypes'));
    }

    public function store(Request $request, int $workspaceId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);

        if (! TenantContext::user()->canAddMoreRooms()) {
            return back()->withInput()->with('error', __('app.plan_limit.rooms'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:meeting,training,shared,office,studio',
            'capacity' => 'required|integer|min:1|max:999',
            'price_per_hour' => 'required|numeric|min:0',
            'billing_unit' => 'nullable|in:minute,half_hour,hour',
            'description' => 'nullable|string|max:1000',
        ]);

        // Only meaningful for shared rooms — force 'minute' for every other
        // type so a stray submitted value can never linger on a room the
        // billing-unit field isn't even shown for.
        $data['billing_unit'] = $data['type'] === 'shared' ? ($data['billing_unit'] ?? 'minute') : 'minute';

        $room = Room::create(array_merge($data, [
            'workspace_id' => $workspace->id,
            'owner_id' => TenantContext::id(),
        ]));

        $this->activityLogger->log('room.created', $room, "Added room {$room->name} to {$workspace->name}");

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room added successfully.');
    }

    public function edit(int $workspaceId, int $roomId): View
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $roomTypes = $this->roomTypeOptions();

        return view('workspaces.rooms.edit', compact('workspace', 'room', 'roomTypes'));
    }

    public function update(Request $request, int $workspaceId, int $roomId, AvailabilityService $availability): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:meeting,training,shared,office,studio',
            'capacity' => 'required|integer|min:1|max:999',
            'price_per_hour' => 'required|numeric|min:0',
            'billing_unit' => 'nullable|in:minute,half_hour,hour',
            'description' => 'nullable|string|max:1000',
        ]);

        $data['billing_unit'] = $data['type'] === 'shared' ? ($data['billing_unit'] ?? 'minute') : 'minute';

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

        $this->activityLogger->log('room.updated', $room, "Updated room {$room->name}");

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $this->activityLogger->log('room.deleted', $room, "Deleted room {$room->name}");

        $room->delete();

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room deleted successfully.');
    }

    public function toggleAvailable(int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room = $this->getRoom($workspace, $roomId);

        $room->update(['is_available' => ! $room->is_available]);

        $this->activityLogger->log('room.availability_toggled', $room, "Room '{$room->name}' marked as ".($room->is_available ? 'available' : 'unavailable'));

        return back()->with(
            'success',
            "Room '{$room->name}' marked as ".($room->is_available ? 'available' : 'unavailable').'.'
        );
    }
}
