@extends('layouts.app')

@section('page-title', __('app.booking.quick_availability'))

@section('content')
    <div class="mb-6">
        <a href="/bookings/calendar" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.btn.back_to_bookings') }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.booking.quick_availability') }}</h1>

    <div class="max-w-xl bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.room') }}</label>
                <select id="lookup-room" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">{{ __('app.placeholder.select_room') }}</option>
                    @php $grouped = $rooms->groupBy(fn($r) => $r->workspace?->name ?? 'Unnamed'); @endphp
                    @foreach ($grouped as $workspaceName => $roomsInGroup)
                        <optgroup label="{{ $workspaceName }}">
                            @foreach ($roomsInGroup as $room)
                                <option value="{{ $room->id }}" data-shared="{{ $room->isShared() ? 'true' : 'false' }}">
                                    {{ $room->name }} — {{ $room->typeLabel() }}
                                    ({{ number_format($room->price_per_hour, 2) }} ج.م{{ __('app.common.slash_hr') }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.date') }}</label>
                <input type="date" id="lookup-date" min="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.session.party_size') }}</label>
                <input type="number" id="lookup-party-size" min="1" value="1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.start_time') }}</label>
                <select id="lookup-start" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">{{ __('app.common.select') }}</option>
                    @foreach ($timeSlots as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.booking.end_time') }}</label>
                <select id="lookup-end" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">{{ __('app.common.select') }}</option>
                    @foreach ($timeSlots as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="button" id="lookup-check" class="mt-5 bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            {{ __('app.booking.check_availability') }}
        </button>

        <div id="lookup-loading" class="hidden mt-4 text-sm text-gray-500">{{ __('app.booking.checking_availability') }}</div>

        <div id="lookup-result" class="hidden mt-5 rounded-lg p-4 text-sm"></div>
    </div>

    <script>
    const roomSelect  = document.getElementById('lookup-room');
    const dateInput   = document.getElementById('lookup-date');
    const startSelect = document.getElementById('lookup-start');
    const endSelect   = document.getElementById('lookup-end');
    const partySize   = document.getElementById('lookup-party-size');
    const resultBox   = document.getElementById('lookup-result');
    const loadingBox  = document.getElementById('lookup-loading');

    document.getElementById('lookup-check').addEventListener('click', () => {
        const roomId = roomSelect.value;
        const date = dateInput.value;
        const startTime = startSelect.value;
        const endTime = endSelect.value;
        const party = partySize.value || 1;

        resultBox.classList.add('hidden');

        if (!roomId || !date || !startTime || !endTime) {
            resultBox.className = 'mt-5 rounded-lg p-4 text-sm bg-yellow-100 text-yellow-800';
            resultBox.textContent = '{{ __('app.booking.fill_all_fields') }}';
            resultBox.classList.remove('hidden');
            return;
        }

        loadingBox.classList.remove('hidden');

        const params = new URLSearchParams({ room_id: roomId, booking_date: date, start_time: startTime, end_time: endTime, party_size: party });

        fetch(`/bookings/check-availability?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                loadingBox.classList.add('hidden');
                resultBox.classList.remove('hidden');

                const remainingLine = data.remaining !== null && data.remaining !== undefined
                    ? `<div class="mt-1">{{ __('app.booking.remaining_capacity') }}: ${data.remaining}</div>`
                    : '';

                if (data.available) {
                    resultBox.className = 'mt-5 rounded-lg p-4 text-sm bg-green-100 text-green-700';
                    resultBox.innerHTML = `
                        <div class="font-semibold">&#10003; {{ __('app.booking.room_available') }}</div>
                        <div class="mt-1">{{ __('app.common.duration') }}: ${data.total_hours} {{ __('app.common.hours') }}</div>
                        <div>{{ __('app.common.total') }}: ج.م ${data.total_price}</div>
                        ${remainingLine}
                    `;
                } else {
                    resultBox.className = 'mt-5 rounded-lg p-4 text-sm bg-red-100 text-red-700';
                    resultBox.innerHTML = `
                        <div class="font-semibold">&#10007; {{ __('app.booking.room_unavailable') }}</div>
                        ${remainingLine}
                    `;
                }
            })
            .catch(() => {
                loadingBox.classList.add('hidden');
            });
    });
    </script>
@endsection
