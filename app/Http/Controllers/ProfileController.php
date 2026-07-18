<?php

namespace App\Http\Controllers;

class ProfileController extends Controller
{
    public function index()
    {
        $owner = auth('owner')->user()->load('plan');
        $usageCount = $owner->hotspotUsers()->count();
        return view('profile.index', compact('owner', 'usageCount'));
    }
}
