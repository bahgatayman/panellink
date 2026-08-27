{{-- $permissions: Collection<group-slug, Collection<Permission>>, $granted: array of currently-granted permission ids --}}
<div class="space-y-4" id="permission-grid">
    @foreach ($permissions as $group => $items)
        <div class="border border-gray-100 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('app.permission_group.'.$group) }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach ($items as $permission)
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                               data-permission-key="{{ $permission->key }}"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               {{ in_array($permission->id, $granted ?? []) ? 'checked' : '' }}>
                        {{ __('app.permission.'.$permission->key) }}
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
