@extends('layouts.admin')

@section('page-title', __('app.section.owners'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.section.owners') }}</h1>
        <a href="/admin/owners/create" class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('app.btn.add_owner') }}
        </a>
    </div>

    <form method="GET" action="/admin/owners" class="mb-6">
        <div class="flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('app.placeholder.search_name_phone') }}"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
            <button type="submit" class="bg-gray-800 text-white px-4 py-2.5 rounded-lg hover:bg-gray-700 transition text-sm font-medium">{{ __('app.common.search') }}</button>
            @if ($search)
                <a href="/admin/owners" class="bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-300 transition text-sm font-medium">{{ __('app.common.clear') }}</a>
            @endif
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 bg-gray-50 border-b border-gray-100">
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.table.th.business') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.table.th.owner') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.table.th.email') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.table.th.status') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.table.th.expires') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.section.users') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.table.th.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($owners as $owner)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="px-4 lg:px-6 py-3 font-medium text-gray-900">{{ $owner->business_name }}</td>
                            <td class="px-4 lg:px-6 py-3 text-gray-700">{{ $owner->name }}</td>
                            <td class="px-4 lg:px-6 py-3 text-gray-500">{{ $owner->email }}</td>
                            <td class="px-4 lg:px-6 py-3">
                                @php $status = $owner->subscriptionStatus(); @endphp
                                @if ($status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('app.status.active') }}</span>
                                @elseif ($status === 'expiring_soon')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('app.status.expiring_soon') }} ({{ $owner->daysUntilExpiry() }}d)</span>
                                @elseif ($status === 'expired')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('app.status.expired') }}</span>
                                @elseif ($status === 'never')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ __('app.status.never_activated') }}</span>
                                @elseif ($status === 'disabled')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('app.status.disabled') }}</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-gray-500">
                                {{ $owner->subscription_expires_at ? $owner->subscription_expires_at->format('Y-m-d') : '—' }}
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-gray-700">{{ $owner->hotspot_users_count }}</td>
                            <td class="px-4 lg:px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="/admin/owners/{{ $owner->id }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">{{ __('app.common.view') }}</a>
                                    <form method="POST" action="/admin/owners/{{ $owner->id }}/toggle-active" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-sm font-medium {{ $owner->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}"
                                                onclick="return confirm('{{ $owner->is_active ? __('app.btn.deactivate') : __('app.btn.activate') }} {{ __('app.admin.owner') }}?')">
                                            {{ $owner->is_active ? __('app.btn.deactivate') : __('app.btn.activate') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 lg:px-6 py-8 text-center text-gray-500">
                                @if ($search)
                                    {{ __('app.empty.no_owners') }} "{{ $search }}".
                                @else
                                    {{ __('app.empty.no_owners_yet') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($owners->hasPages())
            <div class="px-4 lg:px-6 py-3 border-t border-gray-100">
                {{ $owners->links() }}
            </div>
        @endif
    </div>
@endsection
