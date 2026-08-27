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
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.staff.password') }}</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
        document.getElementById('role-select').addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const keys = (selected.dataset.permissionKeys || '').split(',').filter(Boolean);
            document.querySelectorAll('#permission-grid input[type="checkbox"]').forEach(function (box) {
                box.checked = keys.includes(box.dataset.permissionKey);
            });
        });
    </script>
@endsection
