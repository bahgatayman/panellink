@extends('layouts.app')

@section('page-title', __('app.nav.active_sessions'))

@section('content')
@php
    $canSell = $owner->hasFeature('sales');

    // Summary strip — computed from every active session, regardless of the room filter.
    $sumItems = $allSessions->sum(fn ($r) => (float) ($r->sale?->total ?? 0));
    $sumRooms = $allSessions->sum(fn ($r) => $r->isShared() ? (float) ($estimates[$r->model->id]?->totalPrice ?? 0) : (float) $r->model->total_price);
    $endingSoon = $allSessions->filter(fn ($r) => ! $r->isShared() && now()->diffInMinutes($r->model->endsAt(), false) <= 15)->count();
    $seatsUsed = (int) $sharedRooms->sum(fn ($room) => $room->occupied_seats ?? 0);
    $seatsTotal = (int) $sharedRooms->sum('capacity');
@endphp
<div class="ls-page">
    <x-ui.flash />

    <x-ui.page-header :title="__('app.session.active_sessions')" :count="$totalCount" :subtitle="__('app.ui.sessions.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="play" :href="route('active-sessions.create')">{{ __('app.ui.sessions.start_session') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="ls-strip">
        <div>
            <span class="ls-strip-label">{{ __('app.ui.sessions.here_now') }}</span>
            <span class="ls-strip-value is-brand">{{ $totalCount }}</span>
        </div>
        <div>
            <span class="ls-strip-label">{{ __('app.ui.sessions.running_bill') }}</span>
            <span class="ls-strip-value is-revenue"><x-ui.money :amount="$sumRooms + $sumItems" /></span>
            <span class="ls-strip-meta">{{ __('app.ui.sessions.running_bill_meta') }}</span>
        </div>
        @if ($canSell)
            <div class="ls-hide-sm">
                <span class="ls-strip-label">{{ __('app.ui.sessions.products_in_bills') }}</span>
                <span class="ls-strip-value"><x-ui.money :amount="$sumItems" /></span>
            </div>
        @endif
        @if ($sharedRooms->isNotEmpty())
            <div title="@foreach($sharedRooms as $room){{ $room->name }}: {{ $room->occupied_seats ?? 0 }}/{{ $room->capacity }}&#10;@endforeach">
                <span class="ls-strip-label">{{ __('app.ui.sessions.shared_seats') }}</span>
                <span class="ls-strip-value">{{ $seatsUsed }}<span class="ls-faint" style="font-size:14px;font-weight:500"> / {{ $seatsTotal }}</span></span>
                <div class="ls-meter {{ $seatsTotal && $seatsUsed >= $seatsTotal ? 'is-danger' : '' }}" style="margin-top:6px"><i style="width: {{ $seatsTotal ? min(100, $seatsUsed / $seatsTotal * 100) : 0 }}%"></i></div>
            </div>
        @endif
        <div class="{{ $endingSoon ? '' : 'ls-hide-sm' }}">
            <span class="ls-strip-label">{{ __('app.ui.sessions.ending_soon_count') }}</span>
            <span class="ls-strip-value {{ $endingSoon ? 'is-warn' : '' }}">{{ $endingSoon }}</span>
        </div>
    </div>

    @if ($totalCount > 0)
        <div class="ls-toolbar">
            {{-- Room filter — only rooms with at least one active session appear, each with its own live count. --}}
            <nav class="ls-chips is-scroll" aria-label="{{ __('app.session.filter_all_rooms') }}">
                <a href="{{ route('active-sessions.index') }}" class="ls-chip {{ ! $selectedRoomId ? 'is-active' : '' }}" @if(! $selectedRoomId) aria-current="true" @endif>
                    @if (! $selectedRoomId)<x-ui.icon name="check" />@endif
                    {{ __('app.session.filter_all_rooms') }} <span class="ls-chip-count">{{ $totalCount }}</span>
                </a>
                @foreach ($roomCounts as $entry)
                    @php $on = $selectedRoomId === $entry['room']->id; @endphp
                    <a href="{{ route('active-sessions.index', ['room_id' => $entry['room']->id]) }}" class="ls-chip {{ $on ? 'is-active' : '' }}" @if($on) aria-current="true" @endif>
                        @if ($on)<x-ui.icon name="check" />@endif
                        {{ $entry['room']->name }} <span class="ls-chip-count">{{ $entry['count'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="ls-toolbar-spacer"></div>
            <x-ui.search group="sessions" :placeholder="__('app.ui.sessions.search')" width="240px" />
        </div>
    @endif

    @if ($sessions->isEmpty())
        <div class="ls-card">
            <x-ui.empty-state illustration="quiet" :title="__('app.ui.sessions.empty_title')" :text="__('app.empty.no_active_sessions').' '.__('app.ui.sessions.empty_text')">
                <x-ui.button variant="primary" icon="play" :href="route('active-sessions.create')">{{ __('app.ui.sessions.start_session') }}</x-ui.button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="ls-grid-sessions">
            @foreach ($sessions as $row)
                @include('active-sessions._card', [
                    'row' => $row,
                    'estimate' => $row->isShared() ? ($estimates[$row->model->id] ?? 0) : null,
                ])
            @endforeach
        </div>
        <div class="ls-card" data-ls-empty="sessions" hidden>
            <x-ui.empty-state illustration="search" :title="__('app.ui.sessions.no_match_title')" :text="__('app.ui.sessions.no_match_text')" />
        </div>
    @endif
</div>

{{-- ===================== Shared session: add products + check out =====================
     One modal serves both actions (as before); the title, note and primary button reflect
     which one was clicked. All amounts come from the server preview. --}}
<x-ui.modal id="session-modal" :title="__('app.session.close_session')" size="wide">
    <div id="modal-loading" style="display:grid;gap:12px" aria-live="polite">
        <span class="ls-sr">{{ __('app.ui.sessions.loading') }}</span>
        <div style="display:flex;gap:12px;align-items:center"><div class="ls-skel" style="width:40px;height:40px;border-radius:50%"></div><div style="flex:1"><div class="ls-skel" style="width:55%"></div><div class="ls-skel" style="width:35%;margin-top:8px"></div></div></div>
        <div class="ls-skel" style="height:64px"></div>
        <div class="ls-skel" style="width:70%"></div>
    </div>

    <div id="modal-error" hidden>
        <x-ui.banner tone="danger">{{ __('app.ui.sessions.load_failed') }}</x-ui.banner>
        <x-ui.button onclick="retryPreview()">{{ __('app.ui.sessions.try_again') }}</x-ui.button>
    </div>

    <div id="modal-content" hidden style="display:grid;gap:18px">
        <dl class="ls-kv">
            <dt>{{ __('app.session.user') }}</dt><dd id="modal-user"></dd>
            <dt>{{ __('app.session.room') }}</dt><dd id="modal-room"></dd>
            <dt id="modal-party-label" hidden>{{ __('app.session.party_size') }}</dt><dd id="modal-party-row" hidden><span id="modal-party"></span></dd>
            <dt>{{ __('app.session.time') }}</dt><dd><bdi id="modal-time" class="ls-num" dir="ltr"></bdi></dd>
            <dt>{{ __('app.session.duration') }}</dt><dd><span id="modal-duration"></span><span id="modal-billed-row" hidden class="ls-faint" style="display:block;font-size:12px;font-weight:400"><span id="modal-billed"></span></span></dd>
            <dt>{{ __('app.session.rate') }}</dt><dd id="modal-rate"></dd>
        </dl>

        @if ($canSell)
            <div>
                <div class="ls-label" style="margin-bottom:4px">{{ __('app.ui.sessions.on_the_bill') }}</div>
                <div id="modal-items" class="ls-lines"></div>
                @if ($products->isNotEmpty())
                    <div class="ls-add-row" style="margin-top:12px">
                        <div class="ls-field">
                            <label class="ls-label" for="modal-product">{{ __('app.ui.sessions.product') }}</label>
                            <select id="modal-product" class="ls-select">
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} — {{ number_format($p->price, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ls-field">
                            <label class="ls-label" for="modal-qty">{{ __('app.ui.sessions.quantity') }}</label>
                            <div class="ls-stepper"><button type="button" onclick="stepQty('modal-qty',-1)" aria-label="-">−</button><input type="number" id="modal-qty" value="1" min="1" max="1000"><button type="button" onclick="stepQty('modal-qty',1)" aria-label="+">+</button></div>
                        </div>
                        <x-ui.button id="modal-add-btn" icon="plus" onclick="sessionAddItem()">{{ __('app.ui.sessions.add_to_bill') }}</x-ui.button>
                    </div>
                @else
                    <p class="ls-hint" style="margin-top:8px">{{ __('app.ui.sessions.no_catalog') }} <a class="ls-link" href="{{ route('products.create') }}">{{ __('app.ui.sessions.add_catalog_product') }}</a></p>
                @endif
            </div>

            <div class="ls-paper">
                <div style="display:flex;justify-content:space-between"><span class="ls-muted">{{ __('app.ui.sessions.room_time') }}</span><span id="modal-total" class="ls-num"></span></div>
                <div style="display:flex;justify-content:space-between"><span class="ls-muted">{{ __('app.ui.sessions.products') }}</span><span id="modal-items-total" class="ls-num"></span></div>
                <div class="ls-total"><span>{{ __('app.ui.sessions.total_to_collect') }}</span><b id="modal-grand-total"></b></div>
            </div>
        @else
            <div class="ls-total"><span>{{ __('app.ui.sessions.total_to_collect') }}</span><b id="modal-total"></b></div>
        @endif
    </div>

    <x-slot:note><span id="session-modal-note"></span></x-slot:note>
    <x-slot:footer>
        <x-ui.button variant="ghost" id="session-modal-dismiss-btn" onclick="closeSessionModal()">{{ __('app.ui.sessions.keep_open') }}</x-ui.button>
        <div class="ls-push">
            <x-ui.button variant="primary" id="confirm-close-btn" disabled>{{ __('app.session.confirm_save') }}</x-ui.button>
        </div>
    </x-slot:footer>
</x-ui.modal>

{{-- ===================== Exclusive room: add products =====================
     No preview endpoint exists for bookings, so a successful add/remove reloads
     the page (with a toast) rather than patching totals in place. --}}
@if ($canSell)
<x-ui.modal id="booking-items-modal" :title="__('app.session.add_products')" size="wide">
    <div>
        <div class="ls-label" style="margin-bottom:4px">{{ __('app.ui.sessions.on_the_bill') }}</div>
        <div id="booking-modal-items" class="ls-lines"></div>
    </div>
    @if ($products->isNotEmpty())
        <div class="ls-add-row">
            <div class="ls-field">
                <label class="ls-label" for="booking-modal-product">{{ __('app.ui.sessions.product') }}</label>
                <select id="booking-modal-product" class="ls-select">
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} — {{ number_format($p->price, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ls-field">
                <label class="ls-label" for="booking-modal-qty">{{ __('app.ui.sessions.quantity') }}</label>
                <div class="ls-stepper"><button type="button" onclick="stepQty('booking-modal-qty',-1)" aria-label="-">−</button><input type="number" id="booking-modal-qty" value="1" min="1" max="1000"><button type="button" onclick="stepQty('booking-modal-qty',1)" aria-label="+">+</button></div>
            </div>
            <x-ui.button id="booking-add-btn" icon="plus" onclick="bookingAddItem()">{{ __('app.ui.sessions.add_to_bill') }}</x-ui.button>
        </div>
    @else
        <p class="ls-hint">{{ __('app.ui.sessions.no_catalog') }} <a class="ls-link" href="{{ route('products.create') }}">{{ __('app.ui.sessions.add_catalog_product') }}</a></p>
    @endif
    <x-slot:footer>
        <div class="ls-push"><x-ui.button variant="primary" onclick="closeBookingItemsModal()">{{ __('app.ui.sessions.done') }}</x-ui.button></div>
    </x-slot:footer>
</x-ui.modal>
@endif

{{-- ===================== Exclusive room: check out =====================
     A plain form submit, not AJAX: the room charge was fixed at booking time. --}}
<x-ui.modal id="booking-checkout-modal" :title="__('app.session.confirm_check_out')">
    <div class="ls-paper">
        <div style="display:flex;justify-content:space-between"><span class="ls-muted">{{ __('app.ui.sessions.room_price') }}</span><span id="booking-checkout-room-charge" class="ls-num"></span></div>
        <div style="display:flex;justify-content:space-between"><span class="ls-muted">{{ __('app.ui.sessions.products') }}</span><span id="booking-checkout-items-total" class="ls-num"></span></div>
        <div class="ls-total"><span>{{ __('app.ui.sessions.total_to_collect') }}</span><b id="booking-checkout-grand-total"></b></div>
    </div>
    <x-slot:note><span id="booking-checkout-note"></span></x-slot:note>
    <x-slot:footer>
        <x-ui.button variant="ghost" data-ls-close>{{ __('app.ui.sessions.keep_open') }}</x-ui.button>
        <form id="booking-checkout-form" method="POST" action="" class="ls-push" data-ls-busy>
            @csrf
            <input type="hidden" name="status" value="completed">
            <x-ui.button type="submit" variant="primary" id="booking-checkout-submit">{{ __('app.session.confirm_check_out') }}</x-ui.button>
        </form>
    </x-slot:footer>
</x-ui.modal>

<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const RTL = document.documentElement.dir === 'rtl';
    const SALES_ENABLED = @json($canSell);
    const S = @json(__('app.ui.sessions'));
    const L = {
        usedVsBilled: @json(__('app.session.used_vs_billed')),
        noItems: @json(__('app.sales.no_items_yet')),
        remove: @json(__('app.common.delete')),
        closeFailed: @json(__('app.session.failed_to_close_session')),
        titles: { products: @json(__('app.session.add_products')), checkout: @json(__('app.session.close_session')) },
    };
    const fill = (s, map) => Object.entries(map).reduce((acc, [k, v]) => acc.replaceAll(':' + k, v), s);
    const money = (formatted) => RTL ? `${formatted} ج.م` : `EGP ${formatted}`;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = (id) => document.getElementById(id);
    const show = (id, on) => { const el = $(id); if (el) el.hidden = !on; };

    window.stepQty = (id, d) => { const i = $(id); i.value = Math.max(1, Math.min(1000, (parseInt(i.value, 10) || 1) + d)); };

    function renderLines(boxId, items, removeFn) {
        const box = $(boxId);
        if (!items || !items.length) { box.innerHTML = `<p class="ls-hint" style="margin:6px 0 0">${esc(L.noItems)}</p>`; return; }
        box.innerHTML = items.map(it => `
            <div class="ls-line">
                <span class="ls-line-main ls-trunc">${esc(it.name)} <span class="ls-faint">×${esc(it.quantity)}</span></span>
                <span class="ls-num" style="font-weight:500">${esc(money(it.line_total))}</span>
                <button type="button" class="ls-remove" data-remove="${esc(it.id)}" data-name="${esc(it.name)}" aria-label="${esc(L.remove)} ${esc(it.name)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>`).join('');
        box.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => removeFn(b.dataset.remove, b.dataset.name, b)));
    }

    /* ---------------- Shared session modal ---------------- */
    let currentSessionId = null, currentMode = 'checkout', lastPreview = null, itemsChanged = false;

    window.openSessionModal = (sessionId, mode = 'checkout') => {
        currentSessionId = sessionId; currentMode = mode; itemsChanged = false; lastPreview = null;
        $('session-modal-title').textContent = L.titles[mode] || L.titles.checkout;
        $('session-modal-sub').textContent = '';
        const confirmBtn = $('confirm-close-btn');
        confirmBtn.hidden = mode === 'products';
        confirmBtn.disabled = true;
        $('session-modal-dismiss-btn').querySelector('span').textContent = mode === 'products' ? S.done : S.keep_open;
        $('session-modal-note').closest('.ls-dialog-note').hidden = mode === 'products';
        show('modal-loading', true); show('modal-content', false); show('modal-error', false);
        LS.open('session-modal');
        loadPreview();
    };
    window.retryPreview = () => { show('modal-error', false); show('modal-loading', true); loadPreview(); };

    function loadPreview() {
        return fetch(`/shared-sessions/${currentSessionId}/close-preview`, { headers: { 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(data => { populatePreview(data); show('modal-loading', false); show('modal-error', false); show('modal-content', true); })
            .catch(() => { show('modal-loading', false); show('modal-content', false); show('modal-error', true); });
    }

    function populatePreview(data) {
        lastPreview = data;
        const title = currentMode === 'products' ? S.products_title : S.checkout_title;
        $('session-modal-title').textContent = fill(title, { name: data.user_name });
        $('session-modal-sub').textContent = `${data.room_name} · ${data.user_phone}`;
        $('modal-user').textContent = data.user_name;
        $('modal-room').textContent = data.room_name;
        const party = data.party_size > 1;
        show('modal-party-label', party); show('modal-party-row', party);
        $('modal-party').textContent = data.party_size;
        $('modal-time').textContent = data.start_time + ' → ' + data.end_time;
        $('modal-duration').textContent = data.duration;
        // Rule-priced rooms explain the matched package/tier instead of a flat rate.
        $('modal-rate').textContent = data.pricing_note || fill(S.rate_per_hour, { rate: money(data.price_per_hour) });
        $('modal-total').textContent = money(data.total_price);
        show('modal-billed-row', !!data.billed_duration);
        if (data.billed_duration) $('modal-billed').textContent = fill(L.usedVsBilled, { used: data.duration, billed: data.billed_duration });

        const total = SALES_ENABLED ? data.grand_total : data.total_price;
        if (SALES_ENABLED) {
            $('modal-items-total').textContent = money(data.items_total);
            $('modal-grand-total').textContent = money(data.grand_total);
            renderLines('modal-items', data.items, sessionRemoveItem);
        }
        $('session-modal-note').textContent = fill(S.closes_note, { room: data.room_name });
        const confirmBtn = $('confirm-close-btn');
        confirmBtn.querySelector('span').textContent = fill(S.collect, { amount: money(total) });
        confirmBtn.disabled = false;
    }

    window.sessionAddItem = () => {
        const btn = $('modal-add-btn');
        const select = $('modal-product');
        const name = select.options[select.selectedIndex].text.split(' — ')[0];
        LS.busy(btn, true);
        fetch(`/shared-sessions/${currentSessionId}/items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ product_id: select.value, quantity: $('modal-qty').value || 1 }),
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
          .then(() => { itemsChanged = true; $('modal-qty').value = 1; LS.toast(fill(S.added_to_bill, { product: name, name: lastPreview ? lastPreview.user_name : '' })); return loadPreview(); })
          .catch(() => LS.toast(S.action_failed, { tone: 'danger' }))
          .finally(() => LS.busy(btn, false));
    };

    function sessionRemoveItem(itemId, name, btn) {
        btn.disabled = true;
        fetch(`/shared-sessions/${currentSessionId}/items/${itemId}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
          .then(() => { itemsChanged = true; LS.toast(fill(S.removed_from_bill, { product: name })); return loadPreview(); })
          .catch(() => { btn.disabled = false; LS.toast(S.action_failed, { tone: 'danger' }); });
    }

    window.closeSessionModal = () => LS.close('session-modal');
    // Card totals are rendered server-side, so refresh them if the tab changed.
    $('session-modal').addEventListener('ls:close', () => { currentSessionId = null; if (itemsChanged) location.reload(); });

    $('confirm-close-btn').addEventListener('click', function () {
        if (!currentSessionId) return;
        const btn = this, preview = lastPreview;
        LS.busy(btn, true);
        fetch(`/shared-sessions/${currentSessionId}/close`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { LS.busy(btn, false); LS.toast(data.message || L.closeFailed, { tone: 'danger' }); return; }
            const amount = preview ? (SALES_ENABLED ? preview.grand_total : preview.total_price) : '';
            LS.reloadWithToast(fill(S.settled, { amount: money(amount), name: preview ? preview.user_name : '' }));
        })
        .catch(() => { LS.busy(btn, false); LS.toast(L.closeFailed, { tone: 'danger' }); });
    });

    /* ---------------- Exclusive room: add products ---------------- */
    let currentBookingId = null, bookingCustomer = '';
    window.openBookingItemsModal = (bookingId, items, customer) => {
        currentBookingId = bookingId; bookingCustomer = customer || '';
        $('booking-items-modal-title').textContent = customer ? fill(S.products_title, { name: customer }) : L.titles.products;
        renderLines('booking-modal-items', items, bookingRemoveItem);
        LS.open('booking-items-modal');
    };
    window.closeBookingItemsModal = () => LS.close('booking-items-modal');

    window.bookingAddItem = () => {
        const btn = $('booking-add-btn');
        const select = $('booking-modal-product');
        const name = select.options[select.selectedIndex].text.split(' — ')[0];
        LS.busy(btn, true);
        fetch(`/bookings/${currentBookingId}/items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ product_id: select.value, quantity: $('booking-modal-qty').value || 1 }),
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
          .then(() => LS.reloadWithToast(fill(S.added_to_bill, { product: name, name: bookingCustomer })))
          .catch(() => { LS.busy(btn, false); LS.toast(S.action_failed, { tone: 'danger' }); });
    };

    function bookingRemoveItem(itemId, name, btn) {
        btn.disabled = true;
        fetch(`/bookings/${currentBookingId}/items/${itemId}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
          .then(() => LS.reloadWithToast(fill(S.removed_from_bill, { product: name })))
          .catch(() => { btn.disabled = false; LS.toast(S.action_failed, { tone: 'danger' }); });
    }

    /* ---------------- Exclusive room: check out ---------------- */
    window.openBookingCheckoutModal = (bookingId, roomCharge, itemsTotal, grandTotal, customer, room) => {
        $('booking-checkout-modal-title').textContent = customer ? fill(S.checkout_title, { name: customer }) : @json(__('app.session.confirm_check_out'));
        $('booking-checkout-modal-sub').textContent = room || '';
        $('booking-checkout-room-charge').textContent = money(roomCharge);
        $('booking-checkout-items-total').textContent = money(itemsTotal);
        $('booking-checkout-grand-total').textContent = money(grandTotal);
        $('booking-checkout-note').textContent = fill(S.booking_closes_note, { room: room || '' });
        $('booking-checkout-submit').querySelector('span').textContent = fill(S.collect, { amount: money(grandTotal) });
        $('booking-checkout-form').action = `/bookings/${bookingId}/status`;
        LS.open('booking-checkout-modal');
    };
    window.closeBookingCheckoutModal = () => LS.close('booking-checkout-modal');

    /* ---------------- Quick add (one click, one product) ---------------- */
    document.querySelectorAll('.ls-qa[data-qa-kind]').forEach(chip => chip.addEventListener('click', () => {
        const d = chip.dataset;
        const url = d.qaKind === 'shared' ? `/shared-sessions/${d.qaId}/items` : `/bookings/${d.qaId}/items`;
        chip.classList.add('is-busy');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ product_id: d.qaProduct, quantity: 1 }),
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
          .then(() => LS.reloadWithToast(fill(S.added_to_bill, { product: d.qaName, name: d.qaCustomer })))
          .catch(() => { chip.classList.remove('is-busy'); LS.toast(S.action_failed, { tone: 'danger' }); });
    }));

    // Keep amounts honest for block-billed sessions: refresh the page every 5 minutes
    // while nothing is open (per-minute amounts already tick live).
    setInterval(() => { if (!document.querySelector('.ls-overlay.is-open') && !document.activeElement.matches('input')) location.reload(); }, 300000);
})();
</script>
@endsection
