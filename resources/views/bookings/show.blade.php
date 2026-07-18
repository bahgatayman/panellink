@extends('layouts.app')

@section('page-title', __('app.booking.bookings') . ' #' . str_pad($booking->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="/bookings" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.btn.back_to_bookings') }}</a>
        </div>
        @if (in_array($booking->status, ['pending', 'confirmed']))
            <a href="/bookings/{{ $booking->id }}/edit" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                {{ __('app.booking.edit_booking') }}
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.booking.bookings') }} #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ __('app.table.th.created') }} {{ $booking->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @php
                        $colors = ['yellow' => 'bg-yellow-100 text-yellow-800', 'blue' => 'bg-blue-100 text-blue-800', 'green' => 'bg-green-100 text-green-800', 'red' => 'bg-red-100 text-red-800'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $colors[$booking->statusColor()] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $booking->statusLabel() }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.room') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">
                            <a href="/workspaces/{{ $booking->room->workspace->id }}" class="text-blue-600 hover:underline">
                                {{ $booking->room->workspace?->name }}
                            </a>
                            / {{ $booking->room->name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.user') }}</dt>
                        <dd class="font-medium mt-1">
                                    <a href="/users/{{ $booking->hotspotUser->id }}" class="text-blue-600 hover:underline">{{ $booking->hotspotUser->name }}</a>
                                    @if ($booking->hotspotUser->phone)
                                        <span class="text-gray-500"> &middot; {{ $booking->hotspotUser->phone }}</span>
                                    @endif
                                    @if ($booking->hotspotUser->email)
                                        <span class="text-gray-500 block">{{ $booking->hotspotUser->email }}</span>
                                    @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.date') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $booking->booking_date->format('l, M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.common.time') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $booking->timeRange() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.duration') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $booking->total_hours }} {{ __('app.common.hours') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.workspace.price_per_hour') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">ج.م {{ number_format($booking->price_per_hour, 2) }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-gray-500">{{ __('app.booking.total') }}</dt>
                        <dd class="text-2xl font-bold text-blue-600 mt-1">ج.م {{ number_format($booking->total_price, 2) }}</dd>
                    </div>
                </dl>

                @if ($booking->notes)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <dt class="text-sm text-gray-500">{{ __('app.booking.notes') }}</dt>
                        <dd class="text-sm text-gray-900 mt-1">{{ $booking->notes }}</dd>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.common.actions') }}</h3>

                @if ($booking->status === 'pending')
                    <form method="POST" action="/bookings/{{ $booking->id }}/status" class="space-y-2">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            {{ __('app.btn.confirm_booking') }}
                        </button>
                    </form>
                    <form method="POST" action="/bookings/{{ $booking->id }}/status">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="w-full bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition text-sm font-medium"
                                onclick="return confirm('{{ __('app.booking.cancel_booking') }}')">
                            {{ __('app.btn.cancel_booking') }}
                        </button>
                    </form>
                @elseif ($booking->status === 'confirmed')
                    <form method="POST" action="/bookings/{{ $booking->id }}/status" class="space-y-2">
                        @csrf
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                            {{ __('app.btn.mark_completed') }}
                        </button>
                    </form>
                    <form method="POST" action="/bookings/{{ $booking->id }}/status">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="w-full bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition text-sm font-medium"
                                onclick="return confirm('{{ __('app.booking.cancel_booking') }}')">
                            {{ __('app.btn.cancel_booking') }}
                        </button>
                    </form>
                @elseif ($booking->status === 'cancelled')
                    <form method="POST" action="/bookings/{{ $booking->id }}" onsubmit="return confirm('{{ __('app.booking.delete_booking') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                            {{ __('app.btn.delete_booking') }}
                        </button>
                    </form>
                @elseif ($booking->status === 'completed')
                    <p class="text-sm text-green-600 font-medium text-center">{{ __('app.status.completed') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection