@extends('layouts.app')

@section('page-title', __('app.session.shared_sessions'))

@section('content')
    @php $canSell = auth('owner')->user()->hasFeature('sales'); @endphp
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
            @php $occupiedSeats = $room->occupied_seats ?? 0; @endphp
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-500">{{ __('app.session.occupied') }}</span>
                <span class="text-xs font-medium
                    {{ $occupiedSeats >= $room->capacity ? 'text-red-600' : 'text-green-600' }}">
                    {{ $occupiedSeats }}/{{ $room->capacity }}
                </span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                @php
                    $pct = $room->capacity > 0
                        ? ($occupiedSeats / $room->capacity) * 100
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
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $session->hotspotUser->name }}
                                @if ($session->party_size > 1)
                                    <span class="ml-1 inline-flex items-center gap-0.5 text-xs font-normal text-gray-500" title="{{ __('app.session.party_size') }}">
                                        &middot; {{ __('app.session.party_of', ['count' => $session->party_size]) }}
                                    </span>
                                @endif
                            </td>
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
                    <div id="modal-party-row" class="flex justify-between text-sm hidden">
                        <span class="text-gray-500">{{ __('app.session.party_size') }}</span>
                        <span id="modal-party" class="font-medium text-gray-900"></span>
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
                </div>

                @if($canSell)
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-gray-900 mb-2">{{ __('app.sales.items_extras') }}</p>
                        <div id="modal-items" class="space-y-2 mb-3 max-h-40 overflow-y-auto"></div>
                        @if($products->isNotEmpty())
                            <div class="flex items-end gap-2">
                                <select id="modal-product" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} — ج.م {{ number_format($p->price, 2) }}</option>
                                    @endforeach
                                </select>
                                <input type="number" id="modal-qty" value="1" min="1" max="1000"
                                    class="w-16 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="sessionAddItem()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">{{ __('app.sales.add') }}</button>
                            </div>
                        @else
                            <p class="text-xs text-gray-400">
                                {{ __('app.sales.no_products_hint') }}
                                <a href="{{ route('products.create') }}" class="text-blue-600 hover:underline">{{ __('app.sales.add_product') }}</a>
                            </p>
                        @endif
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>{{ __('app.sales.room_charge') }}</span>
                            <span id="modal-total"></span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>{{ __('app.sales.items') }}</span>
                            <span id="modal-items-total"></span>
                        </div>
                        <div class="flex justify-between border-t pt-2 mt-1">
                            <span class="font-bold text-gray-900">{{ __('app.sales.grand_total') }}</span>
                            <span id="modal-grand-total" class="font-bold text-2xl text-blue-600"></span>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-900">{{ __('app.session.total') }}</span>
                            <span id="modal-total" class="font-bold text-2xl text-blue-600"></span>
                        </div>
                    </div>
                @endif

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

    const SALES_ENABLED = @json($canSell);

    function openCloseModal(sessionId) {
        currentSessionId = sessionId;
        document.getElementById('close-modal').classList.remove('hidden');
        document.getElementById('modal-loading').classList.remove('hidden');
        document.getElementById('modal-content').classList.add('hidden');

        fetchPreview().then(() => {
            document.getElementById('modal-loading').classList.add('hidden');
            document.getElementById('modal-content').classList.remove('hidden');
        });
    }

    function fetchPreview() {
        return fetch(`/shared-sessions/${currentSessionId}/close-preview`)
            .then(r => r.json())
            .then(data => { currentCloseData = data; populatePreview(data); });
    }

    function populatePreview(data) {
        document.getElementById('modal-user').textContent     = data.user_name + ' (' + data.user_phone + ')';
        document.getElementById('modal-room').textContent     = data.room_name;

        const partyRow = document.getElementById('modal-party-row');
        if (data.party_size > 1) {
            document.getElementById('modal-party').textContent = data.party_size;
            partyRow.classList.remove('hidden');
        } else {
            partyRow.classList.add('hidden');
        }

        document.getElementById('modal-time').textContent     = data.start_time + ' → ' + data.end_time;
        document.getElementById('modal-duration').textContent = data.duration;
        document.getElementById('modal-rate').textContent     = 'ج.م ' + data.price_per_hour + ' / hr';
        document.getElementById('modal-total').textContent    = 'ج.م ' + data.total_price;

        if (SALES_ENABLED) {
            document.getElementById('modal-items-total').textContent = 'ج.م ' + data.items_total;
            document.getElementById('modal-grand-total').textContent = 'ج.م ' + data.grand_total;
            renderModalItems(data.items);
        }
    }

    function renderModalItems(items) {
        const box = document.getElementById('modal-items');
        if (!items || items.length === 0) {
            box.innerHTML = '<p class="text-xs text-gray-400">{{ __('app.sales.no_items_yet') }}</p>';
            return;
        }
        box.innerHTML = items.map(it =>
            `<div class="flex items-center justify-between text-sm">
                <span class="text-gray-800">${it.name} <span class="text-gray-400">&times;${it.quantity}</span></span>
                <span class="flex items-center gap-2">
                    <span class="text-gray-900 font-medium">ج.م ${it.line_total}</span>
                    <button type="button" onclick="sessionRemoveItem(${it.id})" class="text-gray-400 hover:text-red-600">&times;</button>
                </span>
            </div>`
        ).join('');
    }

    function sessionAddItem() {
        const productId = document.getElementById('modal-product').value;
        const qty       = document.getElementById('modal-qty').value || 1;
        fetch(`/shared-sessions/${currentSessionId}/items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ product_id: productId, quantity: qty }),
        }).then(r => r.json()).then(() => fetchPreview());
    }

    function sessionRemoveItem(itemId) {
        fetch(`/shared-sessions/${currentSessionId}/items/${itemId}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        }).then(r => r.json()).then(() => fetchPreview());
    }

    function closeModal() {
        document.getElementById('close-modal').classList.add('hidden');
        currentSessionId = null;
        currentCloseData = null;
    }

    document.getElementById('confirm-close-btn').addEventListener('click', function() {
        if (!currentSessionId) return;
        this.disabled    = true;
        this.textContent = 'Saving...';

        // The server computes the final total itself (from opened_at → now()) and
        // rejects a session that's already closed — nothing billing-relevant is
        // sent from here anymore.
        fetch(`/shared-sessions/${currentSessionId}/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload();
            } else {
                closeModal();
                alert(data.message || '{{ __('app.session.failed_to_close_session') }}');
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
