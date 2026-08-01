<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\SpeedProfile;
use App\Services\HotspotSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpeedProfileController extends Controller
{
    public function __construct(private HotspotSyncService $sync)
    {
    }

    public function index(): View
    {
        $profiles = SpeedProfile::where('owner_id', auth('owner')->id())
            ->withCount('hotspotUsers')
            ->orderBy('name')
            ->get();

        return view('speed-profiles.index', [
            'profiles' => $profiles,
        ]);
    }

    public function create(): View
    {
        $speedOptions = ['1M', '2M', '5M', '10M', '20M', '50M', '100M'];

        return view('speed-profiles.create', [
            'speedOptions' => $speedOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100|unique:speed_profiles,name,NULL,id,owner_id,' . auth('owner')->id(),
            'speed_download' => 'required|string',
            'speed_upload'   => 'required|string',
            'is_default'     => 'boolean',
        ]);

        $isDefault = $validated['is_default'] ?? false;

        if ($isDefault) {
            SpeedProfile::where('owner_id', auth('owner')->id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $profile = SpeedProfile::create([
            'owner_id'       => auth('owner')->id(),
            'name'           => $validated['name'],
            'speed_download' => $validated['speed_download'],
            'speed_upload'   => $validated['speed_upload'],
            'is_default'     => $isDefault,
        ]);

        $owner = auth('owner')->user();

        try {
            $this->sync->createProfile($owner, $profile->name, $profile->speed_download, $profile->speed_upload);
        } catch (\Exception $e) {
            $profile->delete();
            return back()->withInput()->with('error', "Profile saved but MikroTik sync failed: {$e->getMessage()}. Profile was not created.");
        }

        return redirect('/speed-profiles')->with('success', 'Speed profile created successfully');
    }

    public function edit(int $id): View
    {
        $profile = SpeedProfile::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $speedOptions = ['1M', '2M', '5M', '10M', '20M', '50M', '100M'];

        return view('speed-profiles.edit', [
            'profile' => $profile,
            'speedOptions' => $speedOptions,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $profile = SpeedProfile::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $validated = $request->validate([
            'name'           => 'required|string|max:100|unique:speed_profiles,name,' . $id . ',id,owner_id,' . auth('owner')->id(),
            'speed_download' => 'required|string',
            'speed_upload'   => 'required|string',
            'is_default'     => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            SpeedProfile::where('owner_id', auth('owner')->id())
                ->where('id', '!=', $profile->id)
                ->update(['is_default' => false]);
        }

        $profile->update($validated);

        $owner = auth('owner')->user();

        $assignedUsers = HotspotUser::where('owner_id', auth('owner')->id())
            ->where('speed_profile_id', $profile->id)
            ->get();

        try {
            $syncErrors = $this->sync->syncProfileToUsers($owner, $profile, $assignedUsers);
        } catch (\Exception $e) {
            return back()->with('error', 'Profile updated in DB but MikroTik sync failed: ' . $e->getMessage());
        }

        if (!empty($syncErrors)) {
            return redirect('/speed-profiles')
                ->with('warning', 'Profile updated but some users failed to sync: ' . implode(', ', $syncErrors));
        }

        return redirect('/speed-profiles')
            ->with('success', 'Speed profile updated and synced to ' . ($assignedUsers->count() ?? 0) . ' users on MikroTik.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $profile = SpeedProfile::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $usersUsing = HotspotUser::where('speed_profile_id', $profile->id)->count();

        if ($usersUsing > 0) {
            return back()->with('error', "Cannot delete — {$usersUsing} user(s) are using this profile");
        }

        $owner = auth('owner')->user();

        try {
            $this->sync->deleteProfile($owner, $profile->name);
        } catch (\Exception $e) {
            return back()->with('error', "Could not delete profile from MikroTik: {$e->getMessage()}");
        }

        $profile->delete();

        return redirect('/speed-profiles')->with('success', 'Speed profile deleted successfully');
    }

    public function setDefault(int $id): RedirectResponse
    {
        $profile = SpeedProfile::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        SpeedProfile::where('owner_id', auth('owner')->id())
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $profile->update(['is_default' => true]);

        return back()->with('success', 'Default profile updated successfully');
    }
}
