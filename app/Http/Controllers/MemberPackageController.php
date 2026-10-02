<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\MemberPackage;
use App\Models\PackageTemplate;
use App\Services\ActivityLogger;
use App\Services\HourPackageService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Assign (sell) and cancel a member's hour packages — from the member profile. */
class MemberPackageController extends Controller
{
    public function __construct(private HourPackageService $packages, private ActivityLogger $activityLogger) {}

    /**
     * From a template (its terms prefilled in the modal, still editable) or
     * fully custom. Whatever is submitted here is the snapshot the member keeps.
     */
    public function store(Request $request, int $id): RedirectResponse
    {
        $ownerId = TenantContext::id();
        $member = HotspotUser::where('owner_id', $ownerId)->findOrFail($id);

        $v = $request->validate([
            'package_template_id' => ['nullable', 'integer', Rule::exists('package_templates', 'id')->where('owner_id', $ownerId)],
            'name' => 'required|string|max:80',
            'hours' => 'required|numeric|min:0.25|max:10000',
            'price_paid' => 'required|numeric|min:0|max:9999999.99',
            'starts_on' => 'required|date',
            'expires_on' => 'required|date|after_or_equal:starts_on',
            'notes' => 'nullable|string|max:500',
        ]);

        $template = ! empty($v['package_template_id'])
            ? PackageTemplate::where('owner_id', $ownerId)->findOrFail($v['package_template_id'])
            : null;

        if ($template && ! $template->is_active) {
            return back()->withInput()->with('error', __('app.packages.errors.template_inactive'));
        }

        $pkg = $this->packages->assign($member, [
            'name' => $v['name'],
            'total_minutes' => (int) round((float) $v['hours'] * 60),
            'price_paid' => $v['price_paid'],
            'starts_on' => $v['starts_on'],
            'expires_on' => $v['expires_on'],
            // Room restriction is a template term; custom packages cover every room.
            'room_ids' => $template?->room_ids,
            'notes' => $v['notes'] ?? null,
        ], $template);

        $this->activityLogger->log('package.assigned', $member, "Assigned hour package \"{$pkg->name}\" to {$member->name}");

        return redirect('/users/'.$member->id.'#packages')->with('success', __('app.packages.assigned', ['name' => $pkg->name]));
    }

    /** Stops future use. Usage history and already-covered bookings are kept as they are. */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $pkg = MemberPackage::where('owner_id', TenantContext::id())->with('member')->findOrFail($id);

        if ($pkg->cancelled_at) {
            return back()->with('error', __('app.packages.errors.already_cancelled'));
        }

        $v = $request->validate(['reason' => 'nullable|string|max:255']);
        $this->packages->cancel($pkg, $v['reason'] ?? null);

        $this->activityLogger->log('package.cancelled', $pkg->member, "Cancelled hour package \"{$pkg->name}\" for {$pkg->member?->name}");

        return redirect('/users/'.$pkg->hotspot_user_id.'#packages')->with('success', __('app.packages.cancelled_msg'));
    }
}
