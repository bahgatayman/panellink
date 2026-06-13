<?php

namespace App\Http\Controllers;

use App\Services\MikroTikService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
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

        $service = new MikroTikService(
            $owner->mikrotik_host,
            $owner->mikrotik_port,
            $owner->mikrotik_username,
            $owner->mikrotik_password,
        );

        try {
            $service->connect();

            return back()->with('success', 'Connected to MikroTik successfully');
        } catch (Exception $e) {
            return back()->with('error', "Connection failed: {$e->getMessage()}");
        }
    }
}
