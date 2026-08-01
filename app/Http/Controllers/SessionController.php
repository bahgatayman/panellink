<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Services\HotspotSyncService;
use Exception;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(private HotspotSyncService $sync)
    {
    }

    public function index(): View
    {
        $owner = auth('owner')->user();
        $sessions = [];
        $error = null;

        try {
            $rawSessions = $this->sync->activeUsers($owner);

            $phones = collect($rawSessions)->pluck('phone')->toArray();
            $localUsers = HotspotUser::where('owner_id', $owner->id)
                ->whereIn('phone', $phones)
                ->get()
                ->keyBy('phone');

            $sessions = collect($rawSessions)->map(function ($session) use ($localUsers) {
                $local = $localUsers->get($session['phone'] ?? $session['username'] ?? null);

                return array_merge($session, [
                    'name'    => $local?->name ?? 'Unknown',
                    'user_id' => $local?->id ?? null,
                ]);
            })->toArray();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('sessions.index', [
            'sessions' => $sessions,
            'error'    => $error,
        ]);
    }
}
