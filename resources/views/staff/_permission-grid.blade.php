{{--
    $permissions: Collection<group-slug, Collection<Permission>>, $granted: array of currently-granted permission ids

    Every group in $permissions renders unconditionally — there is no role-
    based filtering here. Selecting a role only bulk-checks/unchecks boxes
    via the page's own <script> (see create.blade.php / edit.blade.php);
    it never hides a group. If this ever renders fewer groups than expected,
    the cause is the `permissions` table missing rows, not this template —
    see database/migrations/2026_09_29_000001_seed_full_permission_catalog.php.
--}}
<div class="ls-permission-grid" id="permission-grid">
    @foreach ($permissions as $group => $items)
        @php $groupIds = $items->pluck('id')->implode(','); @endphp
        <div class="ls-permission-group" data-permission-group>
            <div class="ls-permission-group-head">
                <span class="ls-permission-group-title">
                    {{ __('app.permission_group.'.$group) }}
                    <span class="ls-permission-group-count" data-group-count></span>
                </span>
                <label class="ls-permission-group-all">
                    <input type="checkbox" data-group-toggle data-group-ids="{{ $groupIds }}">
                    {{ __('app.staff.select_all_in_group') }}
                </label>
            </div>
            <div class="ls-permission-items">
                @foreach ($items as $permission)
                    <label class="ls-permission-item">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                               data-permission-key="{{ $permission->key }}"
                               data-permission-item
                               {{ in_array($permission->id, $granted ?? []) ? 'checked' : '' }}>
                        <span>{{ __('app.permission.'.$permission->key) }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
