<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Workspace;
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
            'meeting'  => 'Meeting Room',
            'training' => 'Training Room',
            'shared'   => 'Shared Space',
            'office'   => 'Private Office',
        ];

        return view('workspaces.rooms.create', compact('workspace', 'roomTypes'));
    }

    public function store(Request $request, int $workspaceId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:meeting,training,shared,office',
            'capacity'       => 'required|integer|min:1|max:999',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:1000',
        ]);

        Room::create(array_merge($data, [
            'workspace_id' => $workspace->id,
            'owner_id'     => auth('owner')->id(),
        ]));

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room added successfully.');
    }

    public function edit(int $workspaceId, int $roomId): View
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room      = $this->getRoom($workspace, $roomId);

        $roomTypes = [
            'meeting'  => 'Meeting Room',
            'training' => 'Training Room',
            'shared'   => 'Shared Space',
            'office'   => 'Private Office',
        ];

        return view('workspaces.rooms.edit', compact('workspace', 'room', 'roomTypes'));
    }

    public function update(Request $request, int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room      = $this->getRoom($workspace, $roomId);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:meeting,training,shared,office',
            'capacity'       => 'required|integer|min:1|max:999',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|string|max:1000',
        ]);

        $room->update($data);

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room      = $this->getRoom($workspace, $roomId);

        $room->delete();

        return redirect()->route('workspaces.show', $workspace)
            ->with('success', 'Room deleted successfully.');
    }

    public function toggleAvailable(int $workspaceId, int $roomId): RedirectResponse
    {
        $workspace = $this->getWorkspace($workspaceId);
        $room      = $this->getRoom($workspace, $roomId);

        $room->update(['is_available' => !$room->is_available]);

        return back()->with(
            'success',
            "Room '{$room->name}' marked as " . ($room->is_available ? 'available' : 'unavailable') . '.'
        );
    }
}
