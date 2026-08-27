<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff;
use App\Services\ActivityLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $search = $request->get('search');

        $staff = Staff::where('owner_id', TenantContext::id())
            ->with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('staff.index', compact('staff', 'search'));
    }

    public function create(): View
    {
        $roles = Role::whereNull('owner_id')->orderBy('name')->get();
        $permissions = Permission::where('is_active', true)->orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('staff.create', compact('roles', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStaff($request);

        $staff = Staff::create([
            'owner_id' => TenantContext::id(),
            'role_id' => $validated['role_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $this->applyPermissions($staff, $request);

        $this->activityLogger->log('staff.created', $staff, "Created staff member {$staff->name}");

        return redirect('/staff')->with('success', 'Staff member added successfully.');
    }

    public function edit(Staff $staff): View
    {
        $this->authorizeStaff($staff);

        $roles = Role::whereNull('owner_id')->orderBy('name')->get();
        $permissions = Permission::where('is_active', true)->orderBy('group')->orderBy('name')->get()->groupBy('group');
        $grantedPermissionIds = $staff->permissions()->pluck('permissions.id')->all();

        return view('staff.edit', compact('staff', 'roles', 'permissions', 'grantedPermissionIds'));
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $validated = $this->validateStaff($request, $staff);

        $staff->fill([
            'role_id' => $validated['role_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $staff->password = Hash::make($validated['password']);
        }

        $staff->save();

        $this->applyPermissions($staff, $request);

        $this->activityLogger->log('staff.updated', $staff, "Updated staff member {$staff->name}");

        return redirect('/staff')->with('success', 'Staff member updated successfully.');
    }

    public function toggleStatus(Staff $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $staff->update(['is_active' => ! $staff->is_active]);

        $this->activityLogger->log(
            'staff.status_toggled',
            $staff,
            "{$staff->name} ".($staff->is_active ? 'enabled' : 'disabled')
        );

        $status = $staff->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Staff member {$status}.");
    }

    public function resetPermissions(Staff $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $staff->syncPermissionsFromRole();

        $this->activityLogger->log('staff.permissions_changed', $staff, "Reset {$staff->name}'s permissions to role defaults");

        return back()->with('success', "Permissions reset to {$staff->role?->name} defaults.");
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);

        // Soft delete only — bookings/sales/audit rows created by this staff
        // member must keep a valid actor to point back to.
        $staff->update(['is_active' => false]);
        $staff->delete();

        $this->activityLogger->log('staff.deleted', $staff, "Removed staff member {$staff->name}");

        return redirect('/staff')->with('success', 'Staff member removed.');
    }

    /**
     * Defense in depth: even though /staff/* is only ever reachable via the
     * auth:owner guard (never auth:staff), re-verify the staff row actually
     * belongs to this tenant before it's read or mutated.
     */
    private function authorizeStaff(Staff $staff): void
    {
        abort_unless($staff->owner_id === TenantContext::id(), 404);
    }

    private function validateStaff(Request $request, ?Staff $staff = null): array
    {
        $emailRule = Rule::unique('staff', 'email')->ignore($staff?->id);

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', $emailRule],
            'password' => $staff ? 'nullable|string|min:8' : 'required|string|min:8',
            'role_id' => ['nullable', Rule::exists('roles', 'id')->where(fn ($q) => $q->whereNull('owner_id'))],
        ]);
    }

    /**
     * A submitted role sets the starting bundle; explicitly checked/unchecked
     * permission boxes always win over the role default, matching the
     * "roles + customizable permissions" model — the pivot table, not the
     * role, is the source of truth for what staff can actually do.
     */
    private function applyPermissions(Staff $staff, Request $request): void
    {
        $submitted = collect($request->input('permissions', []))->map(fn ($id) => (int) $id);
        $validIds = Permission::where('is_active', true)->whereIn('id', $submitted)->pluck('id');

        $grants = $validIds->mapWithKeys(fn ($id) => [$id => [
            'granted_at' => now(),
            'granted_by_owner_id' => TenantContext::id(),
        ]])->all();

        $staff->permissions()->sync($grants);
    }
}
