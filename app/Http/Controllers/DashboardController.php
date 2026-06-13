<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\SpeedProfile;
use App\Services\MikroTikService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $totalUsers    = HotspotUser::where('owner_id', $ownerId)->count();
        $activeUsers   = HotspotUser::where('owner_id', $ownerId)->where('status', 'active')->count();
        $totalProfiles = SpeedProfile::where('owner_id', $ownerId)->count();

        $activeSessions = 0;
        $mikrotikError  = null;

        try {
            $mikrotik = new MikroTikService(
                $owner->mikrotik_host,
                $owner->mikrotik_port,
                $owner->mikrotik_username,
                $owner->mikrotik_password,
            );
            $mikrotik->connect();
            $activeSessions = count($mikrotik->getActiveUsers());
            $mikrotik->disconnect();
        } catch (Exception $e) {
            $mikrotikError = $e->getMessage();
        }

        return view('dashboard.index', [
            'owner'          => $owner,
            'totalUsers'     => $totalUsers,
            'activeUsers'    => $activeUsers,
            'totalProfiles'  => $totalProfiles,
            'activeSessions' => $activeSessions,
            'mikrotikError'  => $mikrotikError,
        ]);
    }
}
