@extends('layouts.app')

@section('page-title', __('app.section.staff'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.section.staff') }}</h1>
        <a href="/staff/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            {{ __('app.staff.add_staff') }}
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    <form method="GET" action="/staff" class="mb-6">
        <div class="flex gap-2 max-w-md">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('app.placeholder.search_name_phone') }}"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                {{ __('app.common.search') }}
            </button>
            @if ($search)
                <a href="/staff" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    {{ __('app.common.clear') }}
                </a>
            @endif
        </div>
    </form>

    @if ($staff->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('app.staff.name') }}</th>
                        <th class="px-4 py-3">{{ __('app.staff.email') }}</th>
                        <th class="px-4 py-3">{{ __('app.staff.role') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.status') }}</th>
                        <th class="px-4 py-3">{{ __('app.staff.last_login') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($staff as $member)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="/staff/{{ $member->id }}/edit" class="hover:text-blue-600">{{ $member->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $member->email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $member->role ? __('app.role.'.$member->role->key) : __('app.staff.no_role') }}</td>
                            <td class="px-4 py-3">
                                @if ($member->is_active)
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.status.active') }}</span>
                                @else
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.status.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $member->last_login_at?->format('M d, Y H:i') ?? __('app.staff.never_logged_in') }}
                            </td>
                            <td class="px-4 py-3 flex gap-3">
                                <a href="/staff/{{ $member->id }}/edit" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.common.edit') }}</a>
                                <a href="/staff/{{ $member->id }}/activity" class="text-gray-600 hover:underline text-sm font-medium">{{ __('app.staff.view_activity') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $staff->withQueryString()->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <p class="text-gray-500 text-lg">{{ __('app.staff.no_staff') }}</p>
        </div>
    @endif
@endsection
