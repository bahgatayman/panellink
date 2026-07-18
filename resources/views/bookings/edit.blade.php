@extends('layouts.app')

@section('page-title', __('app.booking.edit_booking') . ' #' . str_pad($booking->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <div class="mb-6">
        <a href="/bookings/{{ $booking->id }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.btn.back_to_bookings') }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.booking.edit_booking') }} #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="/bookings/{{ $booking->id }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.user') }}</label>
                        <select name="hotspot_user_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $booking->hotspot_user_id === $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('hotspot_user_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.room') }}</label>
                        <select name="room_id" id="room_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            @php $grouped = $rooms->groupBy(fn($r) => $r->workspace?->name ?? 'Unnamed'); @endphp
                            @foreach ($grouped as $workspaceName => $roomsInGroup)
                                <optgroup label="{{ $workspaceName }}">
                                    @foreach ($roomsInGroup as $room)
                                        <option value="{{ $room->id }}" {{ $booking->room_id === $room->id ? 'selected' : '' }}
                                            data-type="{{ $room->type }}"
                                            data-shared="{{ $room->isShared() ? 'true' : 'false' }}"
                                            data-price="{{ $room->price_per_hour }}"
                                            {{ $room->isShared() ? 'disabled' : '' }}>
                                            {{ $room->name }} — {{ $room->typeLabel() }}
                                            {{ $room->isShared() ? '(' . __('app.session.open_session') . ')' : '' }}
                                            ({{ number_format($room->price_per_hour, 2) }} ج.م{{ __('app.common.slash_hr') }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('room_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.date') }}</label>
                        <input type="date" name="booking_date" id="booking_date"
                               value="{{ old('booking_date', $booking->booking_date->format('Y-m-d')) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        @error('booking_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.start_time') }}</label>
                        <select name="start_time" id="start_time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            @foreach ($timeSlots as $value => $label)
                                <option value="{{ $value }}" {{ $booking->start_time->format('H:i') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('start_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.end_time') }}</label>
                        <select name="end_time" id="end_time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            @foreach ($timeSlots as $value => $label)
                                <option value="{{ $value }}" {{ $booking->end_time->format('H:i') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('end_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.placeholder.notes_optional') }}</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">{{ old('notes', $booking->notes) }}</textarea>
                    @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                    {{ __('app.common.price') }}: <strong>ج.م {{ number_format($booking->total_price, 2) }}</strong>
                    &mdash; {{ __('app.booking.update_booking') }}
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                        {{ __('app.btn.update_booking') }}
                    </button>
                    <a href="/bookings/{{ $booking->id }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('app.common.cancel') }}</a>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 rounded-xl p-6 sticky top-6 h-fit">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.booking.booking_summary') }}</h3>
            <div id="preview-loading" class="hidden text-sm text-gray-500">{{ __('app.booking.checking_availability') }}</div>
            <div id="preview-content">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('app.booking.duration') }}</span>
                        <span id="preview-hours" class="font-medium">--</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('app.booking.rate') }}</span>
                        <span id="preview-rate" class="font-medium">--</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between">
                        <span class="font-semibold">{{ __('app.booking.total') }}</span>
                        <span id="preview-total" class="font-bold text-blue-600 text-lg">--</span>
                    </div>
                </div>
                <div id="availability-status" class="mt-4 text-sm rounded-lg p-3 hidden"></div>
            </div>
        </div>
    </div>

    <script>
    const roomSelect   = document.getElementById('room_id');
    const dateInput    = document.getElementById('booking_date');
    const startSelect  = document.getElementById('start_time');
    const endSelect    = document.getElementById('end_time');

    function checkAvailability() {
        const roomId    = roomSelect.value;
        const date      = dateInput.value;
        const startTime = startSelect.value;
        const endTime   = endSelect.value;

        if (!roomId || !date || !startTime || !endTime) return;

        document.getElementById('preview-loading').classList.remove('hidden');
        document.getElementById('availability-status').classList.add('hidden');

        fetch(`/bookings/check-availability?room_id=${roomId}&booking_date=${date}&start_time=${startTime}&end_time=${endTime}&booking_id={{ $booking->id }}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('preview-loading').classList.add('hidden');
                const statusDiv = document.getElementById('availability-status');
                statusDiv.classList.remove('hidden');
                if (data.available) {
                    document.getElementById('preview-hours').textContent = data.total_hours + '{{ __('app.common.hours') }}';
                    document.getElementById('preview-rate').textContent  = data.price_per_hour + '{{ __('app.common.slash_hr') }}';
                    document.getElementById('preview-total').textContent = 'ج.م ' + data.total_price;
                    statusDiv.className = 'mt-4 text-sm rounded-lg p-3 bg-green-100 text-green-700';
                    statusDiv.textContent = '\u2713 {{ __('app.booking.room_available') }}';
                } else {
                    document.getElementById('preview-hours').textContent = '--';
                    document.getElementById('preview-rate').textContent  = '--';
                    document.getElementById('preview-total').textContent = '--';
                    statusDiv.className = 'mt-4 text-sm rounded-lg p-3 bg-red-100 text-red-700';
                    statusDiv.textContent = '\u2717 {{ __('app.booking.room_unavailable') }}';
                }
            })
            .catch(() => {
                document.getElementById('preview-loading').classList.add('hidden');
            });
    }

    [roomSelect, dateInput, startSelect, endSelect].forEach(el => {
        el.addEventListener('change', checkAvailability);
    });
    </script>
@endsection