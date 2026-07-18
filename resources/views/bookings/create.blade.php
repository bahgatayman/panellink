@extends('layouts.app')

@section('page-title', __('app.booking.new_booking'))

@section('content')
    <div class="mb-6">
        <a href="/bookings" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.btn.back_to_bookings') }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.booking.new_booking') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="/bookings" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.user') }}</label>
                        <input type="text" id="user-search" placeholder="{{ __('app.placeholder.search_name_phone') }}"
                            autocomplete="off"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <input type="hidden" name="hotspot_user_id" id="selected-user-id" value="{{ old('hotspot_user_id', $selectedUserId ?? '') }}">

                        <div id="search-results"
                             class="hidden absolute z-10 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 w-full">
                        </div>

                        <div id="selected-user-display" class="hidden mt-2 flex items-center gap-2 bg-blue-50 rounded-lg px-3 py-2">
                            <span class="text-sm font-medium text-blue-800" id="selected-user-name"></span>
                            <span class="text-xs text-blue-600" id="selected-user-phone"></span>
                            <button type="button" onclick="clearUserSelection()"
                                class="ml-auto text-xs text-gray-400 hover:text-red-500">&cross;</button>
                        </div>
                        @error('hotspot_user_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.room') }}</label>
                        <select name="room_id" id="room_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('app.placeholder.select_room') }}</option>
                            @php $grouped = $rooms->groupBy(fn($r) => $r->workspace?->name ?? 'Unnamed'); @endphp
                            @foreach ($grouped as $workspaceName => $roomsInGroup)
                                <optgroup label="{{ $workspaceName }}">
                                    @foreach ($roomsInGroup as $room)
                                        <option value="{{ $room->id }}"
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
                               min="{{ now()->format('Y-m-d') }}" value="{{ old('booking_date') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        @error('booking_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.start_time') }}</label>
                        <select name="start_time" id="start_time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('app.common.select') }} {{ __('app.booking.start_time') }}</option>
                            @foreach ($timeSlots as $value => $label)
                                <option value="{{ $value }}" {{ old('start_time') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('start_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.end_time') }}</label>
                        <select name="end_time" id="end_time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('app.common.select') }} {{ __('app.booking.end_time') }}</option>
                            @foreach ($timeSlots as $value => $label)
                                <option value="{{ $value }}" {{ old('end_time') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('end_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.placeholder.notes_optional') }}</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="{{ __('app.placeholder.special_requests') }}">{{ old('notes') }}</textarea>
                    @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                        {{ __('app.btn.confirm_booking') }}
                    </button>
                    <a href="/bookings" class="text-sm text-gray-500 hover:text-gray-700">{{ __('app.common.cancel') }}</a>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 rounded-xl p-6 sticky top-6 h-fit">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.booking.booking_summary') }}</h3>
            <div id="preview-loading" class="hidden text-sm text-gray-500">{{ __('app.booking.checking_availability') }}</div>
            <div id="preview-content">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('app.common.duration') }}</span>
                        <span id="preview-hours" class="font-medium">--</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('app.common.rate') }}</span>
                        <span id="preview-rate" class="font-medium">--</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between">
                        <span class="font-semibold">{{ __('app.common.total') }}</span>
                        <span id="preview-total" class="font-bold text-blue-600 text-lg">--</span>
                    </div>
                </div>
                <div id="availability-status" class="mt-4 text-sm rounded-lg p-3 hidden"></div>
            </div>
        </div>
    </div>

    <script>
    let searchTimeout = null;

    document.getElementById('user-search').addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(searchTimeout);

        if (q.length < 2) {
            document.getElementById('search-results').classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`/users/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(users => {
                    const container = document.getElementById('search-results');
                    if (users.length === 0) {
                        container.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">{{ __('app.empty.no_users') }}</div>';
                    } else {
                        container.innerHTML = users.map(u => `
                            <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-0"
                                 onclick="selectUser(${u.id}, '${u.name}', '${u.phone}')">
                                <p class="text-sm font-medium text-gray-900">${u.name}</p>
                                <p class="text-xs text-gray-500">${u.phone}</p>
                            </div>
                        `).join('');
                    }
                    container.classList.remove('hidden');
                });
        }, 300);
    });

    function selectUser(id, name, phone) {
        document.getElementById('selected-user-id').value    = id;
        document.getElementById('user-search').value         = '';
        document.getElementById('search-results').classList.add('hidden');
        document.getElementById('selected-user-display').classList.remove('hidden');
        document.getElementById('selected-user-name').textContent  = name;
        document.getElementById('selected-user-phone').textContent = phone;
    }

    function clearUserSelection() {
        document.getElementById('selected-user-id').value = '';
        document.getElementById('selected-user-display').classList.add('hidden');
        document.getElementById('user-search').value = '';
        document.getElementById('user-search').focus();
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#user-search') && !e.target.closest('#search-results')) {
            document.getElementById('search-results').classList.add('hidden');
        }
    });

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

        fetch(`/bookings/check-availability?room_id=${roomId}&booking_date=${date}&start_time=${startTime}&end_time=${endTime}`)
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