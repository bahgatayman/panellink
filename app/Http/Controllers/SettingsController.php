<?php

namespace App\Http\Controllers;

use App\Services\HotspotSyncService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private HotspotSyncService $sync)
    {
    }

    public function index(): View
    {
        $owner = Auth::guard('owner')->user();

        return view('settings.index', [
            'owner' => $owner,
        ]);
    }

    /**
     * Save/change the owner's MikroTik router credentials. This is the post-signup
     * write-path (registration no longer collects them). Gated by feature:hotspot.
     * A blank password keeps the stored one.
     */
    public function update(Request $request): RedirectResponse
    {
        $owner = Auth::guard('owner')->user();

        $validated = $request->validate([
            'mikrotik_host'     => 'required|string',
            'mikrotik_port'     => 'required|integer|min:1|max:65535',
            'mikrotik_username' => 'required|string',
            'mikrotik_password' => 'nullable|string',
        ]);

        $owner->update([
            'mikrotik_host'     => $validated['mikrotik_host'],
            'mikrotik_port'     => $validated['mikrotik_port'],
            'mikrotik_username' => $validated['mikrotik_username'],
            'mikrotik_password' => filled($validated['mikrotik_password'] ?? null)
                ? $validated['mikrotik_password']
                : $owner->mikrotik_password,
        ]);

        return back()->with('success', 'Router settings saved successfully.');
    }

    public function testConnection(Request $request): RedirectResponse
    {
        $owner = Auth::guard('owner')->user();

        try {
            $this->sync->testConnection($owner);

            return back()->with('success', 'Connected to MikroTik successfully');
        } catch (Exception $e) {
            return back()->with('error', "Connection failed: {$e->getMessage()}");
        }
    }
}
