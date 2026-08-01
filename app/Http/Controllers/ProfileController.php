<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $owner = auth('owner')->user()->load('plan');
        $usageCount = $owner->hotspotUsers()->count();

        return view('profile.index', compact('owner', 'usageCount'));
    }

    /**
     * Upload (or replace) the owner's brand image.
     *
     * Stored on the `public` disk under owner-logos/. The previous file is
     * removed only after the new path is saved, so a failed write never leaves
     * the owner with a broken image.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $owner = auth('owner')->user();
        $previous = $owner->logo_path;

        $path = $request->file('logo')->store('owner-logos', 'public');

        $owner->update(['logo_path' => $path]);

        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        return back()->with('success', __('app.profile.logo_updated'));
    }

    public function destroyLogo(): RedirectResponse
    {
        $owner = auth('owner')->user();

        if ($owner->logo_path) {
            Storage::disk('public')->delete($owner->logo_path);
            $owner->update(['logo_path' => null]);
        }

        return back()->with('success', __('app.profile.logo_removed'));
    }
}
