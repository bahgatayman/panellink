{{--
    EXPERIMENT — Quick booking modal.
    Booking as a quick action: Who → When (+ people) → Which room → How much → Book.
    Opened from any link to /bookings/create (their ?room_id/booking_date/
    start_time/end_time/hotspot_user_id prefill is honoured); ⌘/Ctrl-click and
    "Open the full booking page" still reach the full form, which is unchanged.

    Sensible defaults do most of the work: today, the next half hour, 1 hour.
    Rooms appear with their live price for exactly that choice, unavailable
    ones say why. Payment and notes stay folded away. There is one total and
    one button — no separate summary.

    Nothing is priced or decided here: prices + availability come from
    GET /bookings/room-options (RoomPricingService / AvailabilityService),
    and the booking is created by the normal POST /bookings (re-validated).
    Remove the @include in layouts/app.blade.php to switch the experiment off.
--}}
@php
    // Same 06:00–23:30 / 30-minute grid as GeneratesTimeSlots (the full form).
    $qbSlots = [];
    for ($t = \Carbon\Carbon::createFromTime(6, 0); $t->lte(\Carbon\Carbon::createFromTime(23, 30)); $t->addMinutes(30)) {
        $qbSlots[$t->format('H:i')] = $t->format('h:i A');
    }
    $qbI18n = [
        'hour1' => trans_choice('app.pricing.hours', 1, ['count' => 1]),
        'hoursN' => trans_choice('app.pricing.hours', 2, ['count' => ':count']),
        'minutes' => __('app.pricing.minutes', ['count' => ':count']),
        'seat1' => trans_choice('app.quick_booking.seats', 1, ['count' => 1]),
        'seatsN' => trans_choice('app.quick_booking.seats', 2, ['count' => ':count']),
        'bookedThen' => __('app.quick_booking.booked_then'),
        'noSeats' => __('app.quick_booking.no_seats'),
        'closed' => __('app.quick_booking.closed'),
        'loading' => __('app.quick_booking.loading'),
        'noRooms' => __('app.quick_booking.no_rooms'),
        'noResults' => __('app.quick_booking.no_results'),
        'addNamed' => __('app.quick_booking.add_customer_named', ['name' => ':name']),
        'addCustomer' => __('app.quick_booking.add_customer'),
        'needCustomer' => __('app.quick_booking.need_customer'),
        'needRoom' => __('app.quick_booking.need_room'),
        'needTime' => __('app.quick_booking.need_time'),
        'cta' => __('app.quick_booking.cta', ['total' => ':total']),
        'ctaIdle' => __('app.quick_booking.cta_idle'),
        'view' => __('app.quick_booking.view'),
        'error' => __('app.quick_booking.error'),
        'sessionLost' => __('app.quick_booking.session_lost'),
        'arrow' => ' → ',
    ];
    $qbCanAddMember = ! ($actingStaff ?? null) || $actingStaff->hasPermission('members.create');
@endphp
<x-ui.modal id="quick-book" :title="__('app.quick_booking.title')" size="wide" class="ls-qb">
    <form id="qb-form" novalidate>
        <div class="ls-banner ls-banner--danger" id="qb-error" role="alert" hidden><x-ui.icon name="alert" /><span></span></div>

        {{-- 1 · Who --}}
        <section class="ls-qb-step" aria-labelledby="qb-who">
            <h4 class="ls-qb-label" id="qb-who"><span class="ls-qb-n" aria-hidden="true">1</span>{{ __('app.quick_booking.customer') }}</h4>

            <div class="ls-qb-picked" id="qb-picked" hidden>
                <span class="ls-avatar ls-avatar--sm" id="qb-picked-avatar" aria-hidden="true"></span>
                <span class="ls-qb-picked-who">
                    <b id="qb-picked-name"></b>
                    <bdi dir="ltr" id="qb-picked-phone" class="ls-faint"></bdi>
                </span>
                <button type="button" class="ls-link" id="qb-change">{{ __('app.quick_booking.change') }}</button>
            </div>

            <div id="qb-find">
                <div class="ls-search">
                    <x-ui.icon name="search" />
                    <input type="text" id="qb-search" class="ls-input" autocomplete="off" spellcheck="false"
                           placeholder="{{ __('app.quick_booking.search') }}" aria-label="{{ __('app.quick_booking.search') }}"
                           role="combobox" aria-expanded="false" aria-controls="qb-results" aria-autocomplete="list">
                </div>
                <ul class="ls-qb-results" id="qb-results" role="listbox" aria-label="{{ __('app.quick_booking.customer') }}" hidden></ul>

                @if ($qbCanAddMember)
                    <div class="ls-qb-newcust" id="qb-newcust" hidden>
                        <input type="text" id="qb-new-name" class="ls-input" placeholder="{{ __('app.user.name') }}" aria-label="{{ __('app.user.name') }}">
                        <input type="text" id="qb-new-phone" class="ls-input" inputmode="tel" dir="ltr" placeholder="{{ __('app.user.phone') }}" aria-label="{{ __('app.user.phone') }}">
                        <button type="button" class="ls-btn ls-btn--tonal" id="qb-new-add">{{ __('app.quick_booking.add') }}</button>
                        <p class="ls-error" id="qb-new-error" hidden></p>
                    </div>
                @endif
            </div>
        </section>

        {{-- 2 · When (+ people, when a room prices by people) --}}
        <section class="ls-qb-step" aria-labelledby="qb-when">
            <h4 class="ls-qb-label" id="qb-when"><span class="ls-qb-n" aria-hidden="true">2</span>{{ __('app.quick_booking.when') }}</h4>

            <div class="ls-chips" role="group" aria-label="{{ __('app.booking.date') }}">
                <button type="button" class="ls-chip" data-qb-day="0" aria-pressed="false">{{ __('app.quick_booking.today') }}</button>
                <button type="button" class="ls-chip" data-qb-day="1" aria-pressed="false">{{ __('app.quick_booking.tomorrow') }}</button>
                <label class="ls-chip ls-qb-datechip" id="qb-date-chip">
                    <input type="date" id="qb-date" min="{{ now()->format('Y-m-d') }}" aria-label="{{ __('app.quick_booking.other_date') }}">
                </label>
            </div>

            <div class="ls-qb-time">
                <label class="ls-qb-field">
                    <span class="ls-qb-field-label">{{ __('app.quick_booking.starts') }}</span>
                    <select id="qb-start" class="ls-select" dir="ltr">
                        @foreach ($qbSlots as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="ls-qb-field ls-qb-grow">
                    <span class="ls-qb-field-label" id="qb-dur-label">{{ __('app.quick_booking.duration') }}</span>
                    <div class="ls-chips" id="qb-durations" role="group" aria-labelledby="qb-dur-label">
                        @foreach ([60, 120, 180, 240] as $m)
                            <button type="button" class="ls-chip" data-qb-minutes="{{ $m }}" aria-pressed="false"></button>
                        @endforeach
                        <button type="button" class="ls-chip" data-qb-minutes="fullday" aria-pressed="false" hidden>{{ __('app.pricing.full_day') }}</button>
                        <button type="button" class="ls-chip" data-qb-minutes="custom" aria-pressed="false">{{ __('app.quick_booking.custom') }}</button>
                    </div>
                </div>

                <label class="ls-qb-field" id="qb-end-wrap" hidden>
                    <span class="ls-qb-field-label">{{ __('app.quick_booking.ends') }}</span>
                    <select id="qb-end" class="ls-select" dir="ltr"></select>
                </label>

                <div class="ls-qb-field" id="qb-people-wrap" hidden>
                    <label class="ls-qb-field-label" for="qb-people">{{ __('app.quick_booking.people') }}</label>
                    <div class="ls-stepper">
                        <button type="button" data-qb-people="-1" aria-label="{{ __('app.quick_booking.people_less') }}">&minus;</button>
                        <input type="number" id="qb-people" min="1" max="999" value="1" inputmode="numeric">
                        <button type="button" data-qb-people="1" aria-label="{{ __('app.quick_booking.people_more') }}">+</button>
                    </div>
                </div>
            </div>
            <p class="ls-qb-range" id="qb-range" aria-live="polite"></p>
        </section>

        {{-- 3 · Room, priced for exactly this choice --}}
        <section class="ls-qb-step" aria-labelledby="qb-room-label">
            <h4 class="ls-qb-label" id="qb-room-label"><span class="ls-qb-n" aria-hidden="true">3</span>{{ __('app.quick_booking.room') }}</h4>
            <p class="ls-qb-msg" id="qb-room-msg" aria-live="polite"></p>
            <div class="ls-qb-rooms" id="qb-rooms" role="radiogroup" aria-labelledby="qb-room-label"></div>
        </section>

        {{-- Optional extras stay folded away. --}}
        <details class="ls-qb-more">
            <summary>{{ __('app.quick_booking.more') }}</summary>
            <div class="ls-qb-more-body">
                <div class="ls-qb-field">
                    <label class="ls-qb-field-label" for="qb-paid">{{ __('app.quick_booking.paid_now') }}</label>
                    <div class="ls-qb-paid">
                        <input type="number" id="qb-paid" class="ls-input" min="0" step="0.01" inputmode="decimal" placeholder="0.00">
                        <button type="button" class="ls-chip" data-qb-paid="none">{{ __('app.quick_booking.paid_none') }}</button>
                        <button type="button" class="ls-chip" data-qb-paid="full">{{ __('app.quick_booking.paid_full') }}</button>
                    </div>
                </div>
                <div class="ls-qb-field">
                    <label class="ls-qb-field-label" for="qb-notes">{{ __('app.quick_booking.note') }}</label>
                    <textarea id="qb-notes" class="ls-textarea" rows="2" maxlength="500" placeholder="{{ __('app.quick_booking.note_placeholder') }}"></textarea>
                </div>
            </div>
        </details>

        <a href="/bookings/create" class="ls-link ls-qb-fullpage" id="qb-fullpage" data-qb-bypass>{{ __('app.quick_booking.full_page') }}</a>
    </form>

    <x-slot:footer>
        <div class="ls-qb-total" aria-live="polite">
            <span class="ls-qb-total-label">{{ __('app.quick_booking.total') }}</span>
            <b class="ls-num" id="qb-total">—</b>
            <small id="qb-hint"></small>
        </div>
        <button type="submit" form="qb-form" class="ls-btn ls-btn--primary ls-qb-cta" id="qb-submit" disabled>{{ __('app.quick_booking.cta_idle') }}</button>
    </x-slot:footer>
</x-ui.modal>

<script>
(function () {
    const T = @json($qbI18n);
    const SLOTS = @json($qbSlots);
    const SLOT_KEYS = Object.keys(SLOTS);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const $ = (id) => document.getElementById(id);
    const fill = (s, map) => Object.entries(map).reduce((o, [k, v]) => o.split(':' + k).join(v), s);

    const S = { user: null, date: '', start: '', minutes: 60, end: '', people: 1, roomId: null, preferRoom: null, rooms: [], fullDay: null, token: 0 };

    // ---------- helpers ----------
    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const dayOffset = (n) => { const d = new Date(); d.setDate(d.getDate() + n); return ymd(d); };
    const toMin = (hm) => { const [h, m] = hm.split(':').map(Number); return h * 60 + m; };
    const toHm = (m) => `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;
    const durLabel = (m) => m < 60 ? fill(T.minutes, { count: m }) : (m === 60 ? T.hour1 : fill(T.hoursN, { count: String(+(m / 60).toFixed(2)) }));
    const endMinutes = () => S.minutes === 'custom' ? (S.end ? toMin(S.end) : null) : S.minutes === 'fullday' ? (S.fullDay ? toMin(S.fullDay.end) : null) : toMin(S.start) + S.minutes;

    function defaultStart() {
        const now = new Date();
        const next = Math.ceil((now.getHours() * 60 + now.getMinutes() + 1) / 30) * 30;
        return SLOT_KEYS.find((k) => toMin(k) >= next) || null;
    }

    // ---------- 1 · customer ----------
    const search = $('qb-search'), results = $('qb-results');
    let searchTimer = null, active = -1, found = [];

    function pickUser(u) {
        S.user = u;
        $('qb-find').hidden = true;
        $('qb-picked').hidden = false;
        $('qb-picked-name').textContent = u.name;
        $('qb-picked-phone').textContent = u.phone || '';
        $('qb-picked-avatar').textContent = (u.name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
        closeResults();
        update();
    }
    $('qb-change').addEventListener('click', () => {
        S.user = null;
        $('qb-picked').hidden = true;
        $('qb-find').hidden = false;
        search.value = '';
        search.focus();
        update();
    });

    function closeResults() { results.hidden = true; search.setAttribute('aria-expanded', 'false'); search.removeAttribute('aria-activedescendant'); active = -1; }
    function renderResults(q) {
        results.innerHTML = '';
        found.forEach((u, i) => {
            const li = document.createElement('li');
            li.id = 'qb-opt-' + i; li.setAttribute('role', 'option'); li.className = 'ls-qb-opt';
            li.innerHTML = '<b></b><bdi dir="ltr" class="ls-faint"></bdi>';
            li.querySelector('b').textContent = u.name; li.querySelector('bdi').textContent = u.phone || '';
            li.addEventListener('mousedown', (e) => { e.preventDefault(); pickUser(u); });
            results.appendChild(li);
        });
        if (!found.length) {
            const li = document.createElement('li'); li.className = 'ls-qb-opt is-empty'; li.textContent = T.noResults; results.appendChild(li);
        }
        if ($('qb-newcust')) {
            const li = document.createElement('li');
            li.id = 'qb-opt-new'; li.setAttribute('role', 'option'); li.className = 'ls-qb-opt is-add';
            li.textContent = '+ ' + (q ? fill(T.addNamed, { name: q }) : T.addCustomer);
            li.addEventListener('mousedown', (e) => { e.preventDefault(); openNewCustomer(q); });
            results.appendChild(li);
        }
        results.hidden = false; search.setAttribute('aria-expanded', 'true');
    }
    function options() { return [...results.querySelectorAll('[role=option]')]; }
    function setActive(i) {
        const opts = options(); if (!opts.length) return;
        active = (i + opts.length) % opts.length;
        opts.forEach((o, n) => o.classList.toggle('is-active', n === active));
        search.setAttribute('aria-activedescendant', opts[active].id);
        opts[active].scrollIntoView({ block: 'nearest' });
    }
    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = search.value.trim();
        if (!q) { closeResults(); return; }
        searchTimer = setTimeout(() => {
            fetch(`/users/search?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json()).then((list) => { found = (list || []).slice(0, 6); renderResults(q); active = -1; })
                .catch(() => {});
        }, 180);
    });
    search.addEventListener('keydown', (e) => {
        if (results.hidden) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
        else if (e.key === 'Enter') { e.preventDefault(); const o = options()[active < 0 ? 0 : active]; if (o) o.dispatchEvent(new MouseEvent('mousedown')); }
        else if (e.key === 'Escape') { e.stopPropagation(); closeResults(); }
    });
    search.addEventListener('blur', () => setTimeout(closeResults, 120));

    function openNewCustomer(q) {
        closeResults();
        const box = $('qb-newcust'); if (!box) return;
        box.hidden = false;
        const digits = /^[+\d\s-]+$/.test(q || '');
        $('qb-new-name').value = digits ? '' : (q || '');
        $('qb-new-phone').value = digits ? q : '';
        (digits || !q ? $('qb-new-name') : $('qb-new-phone')).focus();
    }
    if ($('qb-new-add')) {
        $('qb-new-add').addEventListener('click', () => {
            const err = $('qb-new-error'); err.hidden = true;
            LS.busy($('qb-new-add'));
            fetch('/users/quick', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ name: $('qb-new-name').value.trim(), phone: $('qb-new-phone').value.trim() }),
            })
                .then((r) => r.text().then((t) => { let b = null; try { b = JSON.parse(t); } catch (e) {} return { ok: r.ok, b }; }))
                .then(({ ok, b }) => {
                    if (ok && b) { $('qb-newcust').hidden = true; pickUser({ id: b.id, name: b.name, phone: b.phone }); return; }
                    err.textContent = !b ? T.sessionLost : (b.errors ? Object.values(b.errors)[0][0] : b.message) || T.error;
                    err.hidden = false;
                })
                .catch(() => { err.textContent = T.error; err.hidden = false; })
                .finally(() => LS.busy($('qb-new-add'), false));
        });
    }

    // ---------- 2 · when ----------
    const dateInput = $('qb-date'), startSel = $('qb-start'), endSel = $('qb-end');
    document.querySelectorAll('[data-qb-minutes]').forEach((b) => { if (/^\d+$/.test(b.dataset.qbMinutes)) b.textContent = durLabel(+b.dataset.qbMinutes); });

    function setDate(v) { S.date = v; dateInput.value = v; fetchFullDay(); refreshWhen(); schedule(); }
    document.querySelectorAll('[data-qb-day]').forEach((b) => b.addEventListener('click', () => setDate(dayOffset(+b.dataset.qbDay))));
    dateInput.addEventListener('change', () => { if (dateInput.value) setDate(dateInput.value); });

    startSel.addEventListener('change', () => { S.start = startSel.value; if (S.minutes === 'fullday') S.minutes = 60; refreshWhen(); schedule(); });
    endSel.addEventListener('change', () => { S.end = endSel.value; refreshWhen(); schedule(); });
    document.querySelectorAll('[data-qb-minutes]').forEach((b) => b.addEventListener('click', () => {
        const v = b.dataset.qbMinutes;
        if (v === 'custom') { S.minutes = 'custom'; S.end = toHm(Math.min(toMin(SLOT_KEYS[SLOT_KEYS.length - 1]), (endMinutes() ?? toMin(S.start) + 60))); }
        else if (v === 'fullday') { S.minutes = 'fullday'; S.start = S.fullDay.start; startSel.value = S.start; }
        else S.minutes = +v;
        refreshWhen(); schedule();
        if (v === 'custom') endSel.focus();
    }));

    function refreshWhen() {
        const today = dayOffset(0), tomorrow = dayOffset(1);
        document.querySelectorAll('[data-qb-day]').forEach((b) => {
            const on = S.date === dayOffset(+b.dataset.qbDay);
            b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        $('qb-date-chip').classList.toggle('is-active', !!S.date && S.date !== today && S.date !== tomorrow);

        // End choices: every slot after the start.
        endSel.innerHTML = SLOT_KEYS.filter((k) => toMin(k) > toMin(S.start)).map((k) => `<option value="${k}">${SLOTS[k]}</option>`).join('');
        if (S.minutes === 'custom') {
            if (!S.end || toMin(S.end) <= toMin(S.start)) S.end = endSel.options[0]?.value || '';
            endSel.value = S.end;
        }
        $('qb-end-wrap').hidden = S.minutes !== 'custom';

        const lastSlot = toMin(SLOT_KEYS[SLOT_KEYS.length - 1]);
        document.querySelectorAll('[data-qb-minutes]').forEach((b) => {
            const v = b.dataset.qbMinutes;
            const on = String(S.minutes) === v;
            b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
            if (/^\d+$/.test(v)) b.disabled = toMin(S.start) + +v > lastSlot;
            if (v === 'fullday') b.hidden = !(S.fullDay && SLOTS[S.fullDay.start] && SLOTS[S.fullDay.end]);
        });
        const end = endMinutes();
        // Times isolated left-to-right so Arabic doesn't reorder "04:00 PM → 07:00 PM".
        $('qb-range').textContent = end ? `⁦${SLOTS[S.start]}${T.arrow}${SLOTS[toHm(end)] || toHm(end)}⁩ · ${durLabel(end - toMin(S.start))}` : '';
    }

    function fetchFullDay() {
        const date = S.date;
        fetch(`/bookings/room-options?${new URLSearchParams({ booking_date: date })}`, { headers: { Accept: 'application/json' } })
            .then((r) => r.json()).then((d) => { if (date === S.date) { S.fullDay = d.full_day || null; refreshWhen(); } }).catch(() => {});
    }

    // People (only when some room prices by people).
    const peopleInput = $('qb-people');
    function setPeople(n) { S.people = Math.min(999, Math.max(1, n || 1)); peopleInput.value = S.people; schedule(); }
    document.querySelectorAll('[data-qb-people]').forEach((b) => b.addEventListener('click', () => setPeople(S.people + +b.dataset.qbPeople)));
    peopleInput.addEventListener('input', () => setPeople(parseInt(peopleInput.value, 10)));

    // ---------- 3 · rooms ----------
    let roomTimer = null;
    function schedule() { clearTimeout(roomTimer); roomTimer = setTimeout(fetchRooms, 140); update(); }

    function fetchRooms() {
        const end = endMinutes();
        if (!S.date || !S.start || !end) { S.rooms = []; renderRooms(); return; }
        const token = ++S.token;
        $('qb-room-msg').textContent = T.loading;
        $('qb-rooms').setAttribute('aria-busy', 'true');
        const params = new URLSearchParams({ booking_date: S.date, start_time: S.start, end_time: toHm(end), guest_count: S.people });
        fetch(`/bookings/room-options?${params}`, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => {
                if (token !== S.token) return;
                S.rooms = (d.rooms || []).filter((r) => !r.is_shared);
                if (d.full_day !== undefined) S.fullDay = d.full_day;
                $('qb-people-wrap').hidden = !S.rooms.some((r) => r.uses_people);
                renderRooms();
                refreshWhen();
            })
            .catch(() => { if (token === S.token) { $('qb-room-msg').textContent = T.error; } })
            .finally(() => $('qb-rooms').removeAttribute('aria-busy'));
    }

    function renderRooms() {
        const box = $('qb-rooms'), msg = $('qb-room-msg');
        box.innerHTML = '';
        const outside = S.rooms.length && S.rooms.every((r) => r.reason === 'outside_hours');
        msg.textContent = !S.rooms.length ? (endMinutes() ? T.noRooms : '') : outside ? T.closed : '';
        msg.classList.toggle('is-warn', !!outside);
        if (outside) { S.roomId = null; update(); return; }

        // Available rooms first, then the rest with their reason.
        const sorted = [...S.rooms].sort((a, b) => (a.state === 'unavailable') - (b.state === 'unavailable'));
        if (S.preferRoom && sorted.some((r) => String(r.id) === String(S.preferRoom) && r.state !== 'unavailable')) { S.roomId = String(S.preferRoom); }
        S.preferRoom = null;
        if (S.roomId && !sorted.some((r) => String(r.id) === S.roomId && r.state !== 'unavailable')) S.roomId = null;

        sorted.forEach((r) => {
            const off = r.state === 'unavailable';
            const el = document.createElement('label');
            el.className = 'ls-qb-room' + (off ? ' is-off' : '') + (String(r.id) === S.roomId ? ' is-selected' : '');
            el.innerHTML = `<input type="radio" name="qb-room" value="${r.id}"${off ? ' disabled' : ''}${String(r.id) === S.roomId ? ' checked' : ''}>
                <span class="ls-qb-room-main"><b class="ls-trunc"></b><span class="ls-qb-room-meta ls-trunc"></span></span>
                <span class="ls-qb-room-price"><b class="ls-num"></b><small></small></span>`;
            el.querySelector('b.ls-trunc').textContent = r.name;
            el.querySelector('.ls-qb-room-meta').textContent = `${r.type_label} · ${(r.capacity === 1 ? T.seat1 : fill(T.seatsN, { count: r.capacity }))}`;
            el.querySelector('.ls-qb-room-price b').textContent = off ? '' : r.total_price_display;
            el.querySelector('.ls-qb-room-price small').textContent = off ? (r.reason === 'no_seats' ? T.noSeats : T.bookedThen) : (r.price_note || '');
            el.querySelector('input').addEventListener('change', () => { S.roomId = String(r.id); renderRooms(); });
            box.appendChild(el);
        });
        update();
    }

    // ---------- price + CTA (the one summary) ----------
    const submit = $('qb-submit');
    function currentRoom() { return S.rooms.find((r) => String(r.id) === S.roomId) || null; }
    function update() {
        const room = currentRoom();
        const missing = !S.user ? T.needCustomer : !endMinutes() ? T.needTime : !room ? T.needRoom : '';
        $('qb-total').textContent = room ? room.total_price_display : '—';
        $('qb-hint').textContent = missing || (room && room.price_note) || '';
        submit.disabled = !!missing;
        submit.textContent = room ? fill(T.cta, { total: room.total_price_display }) : T.ctaIdle;
        const paid = $('qb-paid');
        if (room) paid.max = room.total_price;
    }
    document.querySelectorAll('[data-qb-paid]').forEach((b) => b.addEventListener('click', () => {
        const room = currentRoom();
        $('qb-paid').value = b.dataset.qbPaid === 'full' && room ? room.total_price : '';
    }));

    $('qb-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const room = currentRoom(); if (submit.disabled || !room) return;
        const err = $('qb-error'); err.hidden = true;
        const body = new FormData();
        body.append('_token', CSRF);
        body.append('hotspot_user_id', S.user.id);
        body.append('booking_date', S.date);
        body.append('start_time', S.start);
        body.append('end_time', toHm(endMinutes()));
        body.append('room_id', room.id);
        body.append('guest_count', S.people);
        if ($('qb-paid').value) body.append('amount_paid', $('qb-paid').value);
        if ($('qb-notes').value.trim()) body.append('notes', $('qb-notes').value.trim());
        LS.busy(submit);
        fetch('/bookings', { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then((r) => r.text().then((t) => { let b = null; try { b = JSON.parse(t); } catch (x) {} return { ok: r.ok, b }; }))
            .then(({ ok, b }) => {
                if (ok && b && b.success) {
                    LS.close('quick-book');
                    // Pages that list bookings refresh to show it; elsewhere, offer a way there.
                    if (/^\/(bookings(\/calendar)?|dashboard|active-sessions)\/?$/.test(location.pathname)) LS.reloadWithToast(b.message);
                    else LS.toast(b.message, { action: { label: T.view, onClick: () => { location.href = b.url; } } });
                    return;
                }
                err.querySelector('span').textContent = !b ? T.sessionLost : (b.errors ? Object.values(b.errors)[0][0] : b.message) || T.error;
                err.hidden = false;
                err.scrollIntoView({ block: 'nearest' });
                fetchRooms(); // availability may have changed underneath us
            })
            .catch(() => { err.querySelector('span').textContent = T.error; err.hidden = false; })
            .finally(() => LS.busy(submit, false));
    });

    // ---------- open with prefill ----------
    function open(prefill = {}) {
        $('qb-error').hidden = true;
        $('qb-paid').value = ''; $('qb-notes').value = '';
        document.querySelector('.ls-qb-more').open = false;
        if ($('qb-newcust')) $('qb-newcust').hidden = true;
        $('qb-picked-name').textContent = ''; $('qb-picked-phone').textContent = '';
        S.user = null; S.roomId = null; S.rooms = []; S.people = 1; peopleInput.value = 1;
        S.preferRoom = prefill.room_id || null;
        $('qb-picked').hidden = true; $('qb-find').hidden = false; search.value = '';

        let date = prefill.booking_date || dayOffset(0);
        let start = prefill.start_time && SLOTS[prefill.start_time] ? prefill.start_time : null;
        if (!start) { start = date === dayOffset(0) ? defaultStart() : null; }
        if (!start) { if (date === dayOffset(0) && !prefill.booking_date) date = dayOffset(1); start = SLOT_KEYS.includes('10:00') ? '10:00' : SLOT_KEYS[0]; }
        S.date = date; dateInput.value = date; S.start = start; startSel.value = start;
        S.minutes = 60; S.end = '';
        if (prefill.end_time) {
            const m = toMin(prefill.end_time) - toMin(start);
            if ([60, 120, 180, 240].includes(m)) S.minutes = m; else if (m > 0) { S.minutes = 'custom'; S.end = prefill.end_time; }
        }
        $('qb-fullpage').href = '/bookings/create' + (prefill.query ? '?' + prefill.query : '');

        if (prefill.hotspot_user_id && prefill.user_name) pickUser({ id: prefill.hotspot_user_id, name: prefill.user_name, phone: prefill.user_phone || '' });
        refreshWhen(); fetchFullDay(); fetchRooms(); update();
        LS.open('quick-book');
        if (!S.user) setTimeout(() => search.focus(), 60);
    }
    window.LSQuickBook = { open };

    // Any "New booking" link in the app opens the quick flow instead (plain click only).
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || a.hasAttribute('data-qb-bypass') || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const url = new URL(a.href, location.origin);
        if (url.origin !== location.origin || url.pathname !== '/bookings/create') return;
        e.preventDefault();
        const p = Object.fromEntries(url.searchParams);
        open({ ...p, query: url.search.slice(1), user_name: a.dataset.bookName, user_phone: a.dataset.bookPhone });
    });
})();
</script>
