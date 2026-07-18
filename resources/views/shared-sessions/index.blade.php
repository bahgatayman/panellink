@extends('layouts.app')

@section('page-title', __('app.session.shared_sessions'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.session.shared_sessions') }}</h1>
        <a href="{{ route('shared-sessions.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium shadow-sm">
            + {{ __('app.session.open_new_session') }}
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-4">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-4">{{ session('info') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach($sharedRooms as $room)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-sm font-semibold text-gray-900">{{ $room->name }}</p>
            <p class="text-xs text-gray-500 mb-2">{{ $room->workspace->name }}</p>
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-500">{{ __('app.session.occupied') }}</span>
                <span class="text-xs font-medium
                    {{ $room->open_sessions_count >= $room->capacity ? 'text-red-600' : 'text-green-600' }}">
                    {{ $room->open_sessions_count }}/{{ $room->capacity }}
                </span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                @php
                    $pct = $room->capacity > 0
                        ? ($room->open_sessions_count / $room->capacity) * 100
                        : 0;
                @endphp
                <div class="h-1.5 rounded-full
                    {{ $pct >= 100 ? 'bg-red-400' : ($pct >= 70 ? 'bg-yellow-400' : 'bg-green-400') }}"
                    style="width: {{ min(100, $pct) }}%">
                </div>
            </div>
        </div>
        @endforeach
        @if ($sharedRooms->isEmpty())
        <div class="col-span-full text-center py-8 text-gray-400 text-sm">{{ __('app.empty.no_shared_rooms') }}</div>
        @endif
    </div>

    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.session.open_sessions') }}</h2>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if ($openSessions->isEmpty())
            <div class="p-12 text-center">
                <p class="text-gray-400 text-sm">{{ __('app.empty.no_open_sessions') }}</p>
                <a href="{{ route('shared-sessions.create') }}" class="text-green-600 hover:underline text-sm font-medium mt-2 inline-block">{{ __('app.session.open_session') }}</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3 font-medium">{{ __('app.table.th.user') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.table.th.phone') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.table.th.room') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.table.th.workspace') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.table.th.date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.session.start') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.table.th.duration') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('app.session.est_price') }}</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($openSessions as $session)
                        <tr data-opened-at="{{ $session->opened_at->toIso8601String() }}"
                            data-price-per-hour="{{ $session->room->price_per_hour }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $session->hotspotUser->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $session->hotspotUser->phone }}</td>
                            <td class="px-4 py-3">{{ $session->room->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $session->room->workspace->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $session->session_date?->format('M d') ?? $session->opened_at->format('M d') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $session->opened_at->format('h:i A') }}</td>
                            <td class="px-4 py-3 font-medium"><span class="duration-display">--</span></td>
                            <td class="px-4 py-3 font-medium text-blue-700"><span class="price-display">--</span></td>
                            <td class="px-4 py-3">
                                <button onclick="openCloseModal({{ $session->id }})"
                                    class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-lg text-sm font-medium">
                                    {{ __('app.session.close_session') }}
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Close Session Modal --}}
    <div id="close-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-gray-900 mb-6">{{ __('app.session.close_session') }}</h3>

            <div id="modal-loading" class="text-center py-8 text-gray-500">{{ __('app.session.calculating') }}</div>

            <div id="modal-content" class="hidden">
                <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('app.session.user') }}</span>
                        <span id="modal-user" class="font-medium text-gray-900"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('app.session.room') }}</span>
                        <span id="modal-room" class="font-medium text-gray-900"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('app.session.time') }}</span>
                        <span id="modal-time" class="font-medium text-gray-900"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('app.session.duration') }}</span>
                        <span id="modal-duration" class="font-medium text-gray-900"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('app.session.rate') }}</span>
                        <span id="modal-rate" class="font-medium text-gray-900"></span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="font-bold text-gray-900">{{ __('app.session.total') }}</span>
                        <span id="modal-total" class="font-bold text-2xl text-blue-600"></span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button onclick="closeModal()"
                        class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-xl font-medium hover:bg-gray-50">
                        {{ __('app.session.cancel') }}
                    </button>
                    <button id="confirm-close-btn"
                        class="flex-1 bg-blue-600 text-white py-2.5 rounded-xl font-medium hover:bg-blue-700">
                        {{ __('app.session.confirm_save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateDurations() {
        document.querySelectorAll('[data-opened-at]').forEach(el => {
            const openedAt  = new Date(el.dataset.openedAt);
            const now       = new Date();
            const diffMs    = now - openedAt;
            const diffMins  = Math.floor(diffMs / 60000);
            const h         = Math.floor(diffMins / 60);
            const m         = diffMins % 60;
            const duration  = (h > 0 ? h + 'h ' : '') + m + 'm';
            const priceHour = parseFloat(el.dataset.pricePerHour);
            const price     = ((diffMins / 60) * priceHour).toFixed(2);
            el.querySelector('.duration-display').textContent = duration;
            el.querySelector('.price-display').textContent    = 'ج.م ' + price;
        });
    }
    updateDurations();
    setInterval(updateDurations, 30000);

    let currentSessionId  = null;
    let currentCloseData  = null;

    function openCloseModal(sessionId) {
        currentSessionId = sessionId;
        document.getElementById('close-modal').classList.remove('hidden');
        document.getElementById('modal-loading').classList.remove('hidden');
        document.getElementById('modal-content').classList.add('hidden');

        fetch(`/shared-sessions/${sessionId}/close-preview`)
            .then(r => r.json())
            .then(data => {
                currentCloseData = data;
                document.getElementById('modal-user').textContent     = data.user_name + ' (' + data.user_phone + ')';
                document.getElementById('modal-room').textContent     = data.room_name;
                document.getElementById('modal-time').textContent     = data.start_time + ' → ' + data.end_time;
                document.getElementById('modal-duration').textContent = data.duration;
                document.getElementById('modal-rate').textContent     = 'ج.م ' + data.price_per_hour + ' / hr';
                document.getElementById('modal-total').textContent    = 'ج.م ' + data.total_price;

                document.getElementById('modal-loading').classList.add('hidden');
                document.getElementById('modal-content').classList.remove('hidden');
            });
    }

    function closeModal() {
        document.getElementById('close-modal').classList.add('hidden');
        currentSessionId = null;
        currentCloseData = null;
    }

    document.getElementById('confirm-close-btn').addEventListener('click', function() {
        if (!currentSessionId || !currentCloseData) return;
        this.disabled    = true;
        this.textContent = 'Saving...';

        fetch(`/shared-sessions/${currentSessionId}/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                closed_at:     currentCloseData.closed_at_datetime,
                total_minutes: currentCloseData.total_minutes,
                total_price:   currentCloseData.total_price_raw,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload();
            }
        })
        .catch(() => {
            document.getElementById('confirm-close-btn').disabled    = false;
            document.getElementById('confirm-close-btn').textContent = 'Confirm & Save';
            alert('{{ __('app.session.failed_to_close_session') }}');
        });
    });

    document.getElementById('close-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
@endsection
