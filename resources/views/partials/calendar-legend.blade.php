{{--
    Explains the two independent color systems used on the calendar: the
    room timeline (occupancy, per time-block) and booking status pills
    (per booking). These answer different questions and can show the same
    color for different reasons (e.g. red = fully booked on the timeline,
    but red = cancelled on a status pill) — this exists specifically to
    make that legible at a glance rather than left implicit.

    Expects: $showRoomAvailability (bool) — the timeline/slot section only
    makes sense on the day/week views, which are the only ones that render
    partials.booking-day-rooms.
--}}
@php
    $statusPreview = collect(['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'])
        ->map(fn ($status) => new \App\Models\Booking(['status' => $status]));
@endphp

<details class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 group">
    <summary class="cursor-pointer select-none list-none px-4 py-3 flex items-center justify-between text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-xl transition">
        <span class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('app.booking.legend.title') }}
        </span>
        <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </summary>

    <div class="px-4 pb-4 pt-1 border-t border-gray-100 flex flex-wrap gap-x-10 gap-y-4">
        @if ($showRoomAvailability ?? false)
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('app.booking.legend.room_availability') }}</p>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="w-3 h-3 rounded-sm bg-green-200"></span>{{ __('app.booking.legend.free') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="w-3 h-3 rounded-sm bg-yellow-300"></span>{{ __('app.booking.legend.partial') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="w-3 h-3 rounded-sm bg-red-300"></span>{{ __('app.booking.legend.full') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="w-3 h-3 rounded-sm bg-gray-200"></span>{{ __('app.booking.legend.closed') }}
                    </span>
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('app.booking.legend.click_to_book') }}</p>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700">09:00</span>
                        {{ __('app.booking.legend.slot_available') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-400">09:00</span>
                        {{ __('app.booking.legend.slot_unavailable') }}
                    </span>
                </div>
            </div>
        @endif

        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('app.booking.legend.booking_status') }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                @foreach ($statusPreview as $preview)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $preview->statusBadgeClass() }}">
                        {{ $preview->statusLabel() }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</details>
