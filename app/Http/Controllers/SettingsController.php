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
