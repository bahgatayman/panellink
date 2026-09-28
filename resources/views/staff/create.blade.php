@extends('layouts.app')

@section('page-title', __('app.staff.add_staff'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.staff.add_staff') }}</h1>
        <a href="/staff" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.common.back') }}</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/staff" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-3xl space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.staff.name') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.staff.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="staff-password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.staff.password') }}</label>
                <x-ui.password name="password" id="staff-password" required minlength="8"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.staff.role') }}</label>
                <select name="role_id" id="role-select"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">{{ __('app.staff.no_role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}"
                                data-permission-keys="{{ $role->permissions->pluck('key')->implode(',') }}">
                            {{ __('app.role.'.$role->key) }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">{{ __('app.staff.role_hint') }}</p>
            </div>
        </div>

        <div>
            <p class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.staff.permissions') }}</p>
            @include('staff._permission-grid', ['permissions' => $permissions, 'granted' => []])
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                {{ __('app.staff.save') }}
            </button>
            <a href="/staff" class="px-5 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                {{ __('app.common.cancel') }}
            </a>
        </div>
    </form>

    <script>
        (function () {
            const grid = document.getElementById('permission-grid');

            function refreshGroup(group) {
                const items = [...group.querySelectorAll('[data-permission-item]')];
                const checked = items.filter(box => box.checked).length;
                const toggle = group.querySelector('[data-group-toggle]');
                toggle.checked = checked > 0 && checked === items.length;
                toggle.indeterminate = checked > 0 && checked < items.length;
                group.querySelector('[data-group-count]').textContent = `${checked}/${items.length}`;
            }

            function refreshAllGroups() {
                grid.querySelectorAll('[data-permission-group]').forEach(refreshGroup);
            }

            grid.addEventListener('change', function (e) {
                if (e.target.matches('[data-group-toggle]')) {
                    const ids = (e.target.dataset.groupIds || '').split(',').filter(Boolean);
                    ids.forEach(id => {
                        const box = grid.querySelector(`[data-permission-item][value="${id}"]`);
                        if (box) box.checked = e.target.checked;
                    });
                    refreshAllGroups();
                    return;
                }
                if (e.target.matches('[data-permission-item]')) {
                    refreshGroup(e.target.closest('[data-permission-group]'));
                }
            });

            document.getElementById('role-select').addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                const keys = (selected.dataset.permissionKeys || '').split(',').filter(Boolean);
                grid.querySelectorAll('[data-permission-item]').forEach(function (box) {
                    box.checked = keys.includes(box.dataset.permissionKey);
                });
                refreshAllGroups();
            });

            refreshAllGroups();
        })();
    </script>
@endsection
