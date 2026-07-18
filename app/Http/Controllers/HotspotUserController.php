<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\SpeedProfile;
use App\Services\MikroTikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HotspotUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $users = HotspotUser::where('owner_id', auth('owner')->id())
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:hotspot_users,phone',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $phone = (string) $validated['phone'];
        $password = $phone;

        $owner = auth('owner')->user();

        if (!$owner->plan) {
            return back()->withInput()->with('error',
                'No active plan assigned. Please contact your administrator.');
        }

        if (!$owner->canAddMoreUsers()) {
            return back()->withInput()->with('error',
                "You have reached your plan limit of {$owner->plan->max_members} members. Please upgrade your plan to add more users.");
        }

        $defaultProfile = SpeedProfile::where('owner_id', $owner->id)
            ->where('is_default', true)
            ->first();

        if (!$defaultProfile) {
            return back()->withInput()->with('error', 'Please set a default speed profile first before adding users.');
        }

        $mikrotik = new MikroTikService(
            $owner->mikrotik_host,
            $owner->mikrotik_port,
            $owner->mikrotik_username,
            $owner->mikrotik_password,
        );

        try {
            $mikrotik->connect();
            $mikrotik->createHotspotUser($phone, $password, $defaultProfile->name);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'MikroTik error: ' . $e->getMessage());
        } finally {
            $mikrotik->disconnect();
        }

        HotspotUser::create([
            'owner_id'         => $owner->id,
            'name'             => $validated['name'],
            'phone'            => $phone,
            'password'         => $password,
            'speed_download'   => $defaultProfile->speed_download,
            'speed_upload'     => $defaultProfile->speed_upload,
            'speed_profile_id' => $defaultProfile->id,
            'status'           => 'active',
            'email'            => $validated['email'] ?? null,
            'notes'            => $validated['notes'] ?? null,
        ]);

        return redirect('/users')->with('success', "User {$validated['name']} added successfully");
    }

    public function show(int $id): View
    {
        $user = HotspotUser::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $speedProfiles = SpeedProfile::where('owner_id', auth('owner')->id())->get();

        $recentBookings = $user->bookings()
            ->with('room.workspace')
            ->latest()
            ->take(5)
            ->get();

        return view('users.show', [
            'user' => $user,
            'speedProfiles' => $speedProfiles,
            'recentBookings' => $recentBookings,
        ]);
    }

    public function edit(int $id): View
    {
        $user = HotspotUser::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        return view('users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = HotspotUser::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'email'  => 'nullable|email|max:255',
            'notes'  => 'nullable|string|max:500',
        ]);

        $user->update([
            'name'   => $validated['name'],
            'status' => $validated['status'],
            'email'  => $validated['email'] ?? $user->email,
            'notes'  => $validated['notes'] ?? $user->notes,
        ]);

        return redirect("/users/{$user->id}")->with('success', 'User updated successfully');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = HotspotUser::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $owner = auth('owner')->user();

        $mikrotik = new MikroTikService(
            $owner->mikrotik_host,
            $owner->mikrotik_port,
            $owner->mikrotik_username,
            $owner->mikrotik_password,
        );

        try {
            $mikrotik->connect();
            $mikrotik->deleteHotspotUser($user->phone);
        } catch (\Exception $e) {
            return back()->with('error', "Could not delete user from MikroTik: {$e->getMessage()}");
        } finally {
            $mikrotik->disconnect();
        }

        $user->delete();

        return redirect('/users')->with('success', 'User deleted successfully');
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $user = HotspotUser::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'User status updated successfully');
    }

    public function updateSpeed(Request $request, int $id): RedirectResponse
    {
        $user = HotspotUser::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $validated = $request->validate([
            'speed_profile_id' => 'required|exists:speed_profiles,id',
        ]);

        $profile = SpeedProfile::where('id', $validated['speed_profile_id'])
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $owner = auth('owner')->user();

        $mikrotik = new MikroTikService(
            $owner->mikrotik_host,
            $owner->mikrotik_port,
            $owner->mikrotik_username,
            $owner->mikrotik_password,
        );

        try {
            $mikrotik->connect();
            $mikrotik->setUserSpeed($user->phone, $profile->name);
        } catch (\Exception $e) {
            return back()->with('error', 'MikroTik error: ' . $e->getMessage());
        } finally {
            $mikrotik->disconnect();
        }

        $user->update([
            'speed_download'   => $profile->speed_download,
            'speed_upload'     => $profile->speed_upload,
            'speed_profile_id' => $profile->id,
        ]);

        return back()->with('success', 'Speed updated successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $users = HotspotUser::where('owner_id', auth('owner')->id())
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
