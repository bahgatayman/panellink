@extends('layouts.app')

@section('page-title', $user->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
        <div class="flex gap-2">
            <a href="/users/{{ $user->id }}/edit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition text-sm font-medium">
                {{ __('app.common.edit') }}
            </a>
            <form method="POST" action="/users/{{ $user->id }}" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                    {{ __('app.common.delete') }}
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.user.name') }}</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.user.phone') }} ({{ __('app.auth.username') }})</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->phone }}</dd>
                </div>
                @if ($user->email)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.user.email') }}</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->email }}</dd>
                </div>
                @endif
                @if ($user->notes)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.user.notes') }}</dt>
                    <dd class="text-sm text-gray-600">{{ $user->notes }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.label.speed_download') }}</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->speed_download }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.label.speed_upload') }}</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->speed_upload }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.common.status') }}</dt>
                    <dd>
                        @if ($user->status === 'active')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.status.active') }}</span>
                        @else
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.status.inactive') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">{{ __('app.user.created') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $user->created_at->format('M d, Y h:i A') }}</dd>
                </div>
            </dl>

            <div class="mt-6 pt-4 border-t">
                <form method="POST" action="/users/{{ $user->id }}/toggle-status">
                    @csrf
                    <button type="submit" class="w-full {{ $user->status === 'active' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600' }} text-white px-4 py-2 rounded-lg transition text-sm font-medium">
                        {{ $user->status === 'active' ? __('app.btn.deactivate') : __('app.btn.activate') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.user.change_speed') }}</h2>

            <form method="POST" action="/users/{{ $user->id }}/speed">
                @csrf

                <div class="mb-4">
                    <label for="speed_profile_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.user.select_speed_profile') }}</label>
                    <select name="speed_profile_id" id="speed_profile_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">{{ __('app.placeholder.select_profile') }}</option>
                        @foreach ($speedProfiles as $profile)
                            <option value="{{ $profile->id }}" {{ $user->speed_profile_id == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }} (&darr;{{ $profile->speed_download }} / &uarr;{{ $profile->speed_upload }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <p class="text-xs text-gray-400 mb-4">{{ __('app.user.speed_change_hint') }}</p>

                <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-sm">
                    {{ __('app.user.update_speed_on_mikrotik') }}
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.user.recent_bookings') }}</h2>

            @if ($recentBookings->isEmpty())
                <p class="text-sm text-gray-400">{{ __('app.empty.no_bookings') }}</p>
                <a href="/bookings/create?hotspot_user_id={{ $user->id }}" class="text-blue-600 hover:underline text-sm font-medium mt-2 inline-block">
                    {{ __('app.booking.new_booking') }}
                </a>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-3 font-medium">{{ __('app.table.th.date') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('app.table.th.room') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('app.table.th.hours') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('app.table.th.total') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('app.table.th.status') }}</th>
                                <th class="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentBookings as $booking)
                                <tr class="border-b border-gray-50">
                                    <td class="px-4 py-3 font-medium">{{ $booking->booking_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">{{ $booking->room->workspace?->name }} / {{ $booking->room->name }}</td>
                                    <td class="px-4 py-3">{{ $booking->total_hours }} hrs</td>
                                    <td class="px-4 py-3 font-medium">ج.م {{ number_format($booking->total_price, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @php $colors = ['yellow' => 'bg-yellow-100 text-yellow-800', 'blue' => 'bg-blue-100 text-blue-800', 'green' => 'bg-green-100 text-green-800', 'red' => 'bg-red-100 text-red-800']; @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colors[$booking->statusColor()] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $booking->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="/bookings/{{ $booking->id }}" class="text-blue-600 hover:underline text-xs font-medium">{{ __('app.common.view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        </div>
    </div>
@endsection
