{{--
    Per-room booked/available timeline + that day's bookings. Shared by the
    day view and each day of the week view so both render identically.
    Expects: $dayRooms (array of ['room','blocks','bookings','live']).

    Each entry's timeline width is derived from its own $blocks span rather
    than a fixed constant: once an owner configures working hours, blocks
    can span the whole day (a closed span is still returned, not omitted —
    see AvailabilityService::freeBusyForDay()), and different dates in the
    week view can have different hours.

    The click-to-book pills are absolutely positioned on the SAME
    entryStart/entryEnd/totalMinutes scale as the bar above them (not
    flex-wrapped, which was never actually aligned to the bar — flex-wrap
    sizes each pill by its own text, not by duration, and bookableSlots()'s
    own span only covers the open-hours envelope while the bar can span the
    full day). Both live inside one shared overflow-x-auto rail with a
    min-width scaled to the time span so a hovered pill has room for its
    label instead of being crushed — on narrow viewports the rail scrolls
    instead of cramming, matching this app's existing wide-table-scroller
    convention (see ResponsiveLayoutTest). `inset-inline-start`, not `left`,
    since it must flip with the page's RTL/LTR direction the same way the
    bar's own flex layout already does — a plain `left: X%` would stay
    physically fixed under `dir="rtl"` and drift out of sync with the bar.
--}}
@php
    $toMinutes = function (string $time) {
        if (str_starts_with($time, '24:00')) {
            return 24 * 60;
        }
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    };
    $typeColors = ['blue' => 'bg-blue-100 text-blue-700', 'purple' => 'bg-purple-100 text-purple-700', 'green' => 'bg-green-100 text-green-700', 'orange' => 'bg-orange-100 text-orange-700', 'gray' => 'bg-gray-100 text-gray-700'];
@endphp

<div class="space-y-3">
    @forelse ($dayRooms as $entry)
        @php
            $room = $entry['room'];
            $blocks = $entry['blocks'];
            $slots = $entry['slots'] ?? [];
            $bookings = $entry['bookings'];
            $live = $entry['live'];
            $entryDate = $entry['date'];
        @endphp
        <div class="border border-gray-100 rounded-lg p-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-900">{{ $room->workspace?->name }} / {{ $room->name }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $typeColors[$room->typeColor()] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $room->typeLabel() }}
                    </span>
                </div>
                @if ($live)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                        {{ __('app.booking.live_now', ['occupied' => $live['occupied'], 'capacity' => $live['capacity']]) }}
                    </span>
                @endif
            </div>

            @php
                $entryStartMin = ! empty($blocks) ? $toMinutes($blocks[0]['start']) : 0;
                $entryEndMin = ! empty($blocks) ? $toMinutes(end($blocks)['end']) : 0;
                $totalMinutes = $entryEndMin - $entryStartMin;
                // 56px per hour gives an "h:i A" pill (e.g. "09:00 AM") room
                // to render without truncating; the rail scrolls below that
                // instead of cramming every pill on narrow viewports.
                $railMinWidth = $totalMinutes > 0 ? max(320, ($totalMinutes / 60) * 56) : 320;
            @endphp
            <div class="overflow-x-auto pb-1 mb-3">
                <div style="min-width: {{ $railMinWidth }}px;">
                    <div class="flex w-full h-3 rounded overflow-hidden border border-gray-100">
                        @foreach ($blocks as $block)
                            @php
                                $width = $totalMinutes > 0 ? max(1, (($toMinutes($block['end']) - $toMinutes($block['start'])) / $totalMinutes) * 100) : 0;
                                $closed = $block['closed'] ?? false;
                                $color = $closed ? 'bg-gray-200' : ($block['used'] === 0 ? 'bg-green-200' : ($block['available'] > 0 ? 'bg-yellow-300' : 'bg-red-300'));
                                $tooltip = $closed
                                    ? "{$block['start']}–{$block['end']}: ".__('app.settings.working_hours.closed')
                                    : "{$block['start']}–{$block['end']}: {$block['used']}/{$block['capacity']} ".__('app.session.occupied');
                            @endphp
                            <div class="{{ $color }} h-full" style="width: {{ $width }}%" title="{{ $tooltip }}"></div>
                        @endforeach
                    </div>

                    @if (! empty($slots))
                        {{--
                            Click-to-book: each pill opens the existing create-booking
                            form pre-filled with this room/date/time. This is a
                            convenience shortcut only — the calendar's availability is
                            never treated as authorization to book, store() always
                            re-validates capacity server-side against the live data.
                            Positioned on the exact same scale as the bar above (see
                            the partial's top-of-file comment) — not a flex-wrapped
                            list, so a pill sits directly under its matching segment.
                        --}}
                        <div class="relative w-full h-6 mt-1.5">
                            @foreach ($slots as $slot)
                                @php
                                    $slotStartMin = $toMinutes($slot['start']);
                                    $slotEndMin = $toMinutes($slot['end']);
                                    $leftPct = $totalMinutes > 0 ? (($slotStartMin - $entryStartMin) / $totalMinutes) * 100 : 0;
                                    $widthPct = $totalMinutes > 0 ? (($slotEndMin - $slotStartMin) / $totalMinutes) * 100 : 0;
                                    $slotLabel = \Carbon\Carbon::createFromFormat('H:i', $slot['start'])->format('h:i A');
                                    $slotStyle = "inset-inline-start: {$leftPct}%; width: calc({$widthPct}% - 2px);";
                                @endphp
                                @if ($slot['available'])
                                    <a href="/bookings/create?{{ http_build_query(['room_id' => $room->id, 'booking_date' => $entryDate, 'start_time' => $slot['start'], 'end_time' => $slot['end']]) }}"
                                       class="absolute top-0 h-full flex items-center justify-center rounded text-[10px] font-medium bg-green-100 text-green-700 hover:bg-green-600 hover:text-white transition overflow-hidden"
                                       style="{{ $slotStyle }}"
                                       title="{{ __('app.booking.book_this_slot') }} — {{ $slotLabel }}">
                                        <span class="truncate px-0.5">{{ $slotLabel }}</span>
                                    </a>
                                @else
                                    <span class="absolute top-0 h-full flex items-center justify-center rounded text-[10px] font-medium bg-gray-100 text-gray-400 cursor-not-allowed overflow-hidden"
                                          style="{{ $slotStyle }}"
                                          title="{{ $slotLabel }}">
                                        <span class="truncate px-0.5">{{ $slotLabel }}</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($bookings->isEmpty())
                <p class="text-xs text-gray-400">{{ __('app.empty.no_bookings_on_day') }}</p>
            @else
                <div class="space-y-1">
                    @foreach ($bookings as $booking)
                        <a href="/bookings/{{ $booking->id }}" class="flex items-center justify-between gap-2 px-2 py-1.5 rounded hover:bg-gray-50 text-sm transition">
                            <span class="font-medium text-gray-700 whitespace-nowrap">{{ $booking->timeRange() }}</span>
                            <span class="text-gray-600 flex-1 truncate px-2">
                                {{ $booking->hotspotUser->name }}
                                @if ($booking->party_size > 1)
                                    <span class="text-gray-400 text-xs">&middot; {{ __('app.session.party_of', ['count' => $booking->party_size]) }}</span>
                                @endif
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $booking->statusBadgeClass() }}">
                                {{ $booking->statusLabel() }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-400 text-center py-6">{{ __('app.empty.no_rooms_for_calendar') }}</p>
    @endforelse
</div>
