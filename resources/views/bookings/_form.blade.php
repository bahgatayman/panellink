{{--
    Shared by bookings/create.blade.php and edit.blade.php. Who → When →
    Where → How much → Confirm — Customer first (the operator's natural
    starting point), then a compact Reservation row, Room cards, Payment,
    and a sticky/bottom checkout-style summary. Only Customer/Room get a
    bordered card (they're interactive selections); Reservation/Payment are
    lighter, borderless sections so the page reads as a workstation, not a
    stack of form cards.

    Reuses, unmodified: <x-ui.time-picker> (the grid popover + panel.js's
    existing data-ls-menu engine), the duration-chip pattern, and
    checkAvailability()'s replacement /bookings/room-options — the server
    stays the only source of truth for pricing/availability. Nothing here
    changes what start_time/end_time/room_id/hotspot_user_id/amount_paid
    mean to the controller: same field names, same validation.

    Expects (from create()/edit()): $rooms, $timeSlots, and either $booking
    (edit) or the old()/$selected* prefill variables (create).
--}}
@php
    $isEdit = isset($booking) && $booking;
    $initialUserId = $isEdit ? $booking->hotspot_user_id : old('hotspot_user_id', $selectedUserId ?? '');
    $initialRoomId = (string) ($isEdit ? $booking->room_id : old('room_id', $selectedRoomId ?? ''));
    $initialDate = $isEdit ? $booking->booking_date->format('Y-m-d') : old('booking_date', $selectedDate ?? '');
    $initialStart = $isEdit ? \Carbon\Carbon::parse($booking->start_time)->format('H:i') : old('start_time', $selectedStartTime ?? '');
    $initialEnd = $isEdit ? \Carbon\Carbon::parse($booking->end_time)->format('H:i') : old('end_time', $selectedEndTime ?? '');
    $initialAmountPaid = $isEdit ? old('amount_paid', (float) $booking->amount_paid) : old('amount_paid', '');
    $initialNotes = $isEdit ? old('notes', $booking->notes) : old('notes');
    $actionUrl = $isEdit ? "/bookings/{$booking->id}" : '/bookings';

    // Spelled out ("30 min", "1.5 hr") rather than the compact "1h 30m"
    // suffixes used elsewhere (e.g. active-session elapsed time) — this is
    // the primary duration control, not a secondary readout, so it gets the
    // more legible form. Arabic keeps the existing numeral+suffix
    // convention (unit_h/unit_m) already used throughout the app.
    $isRtl = app()->getLocale() === 'ar';
    $durationOptions = collect([30, 60, 90, 120, 180, 240])->map(function ($mins) use ($isRtl) {
        if ($isRtl) {
            $label = $mins < 60
                ? $mins.__('app.ui.unit_m')
                : rtrim(rtrim(number_format($mins / 60, 1), '0'), '.').__('app.ui.unit_h');
        } else {
            $label = $mins < 60
                ? $mins.' '.__('app.common.min')
                : rtrim(rtrim(number_format($mins / 60, 1), '0'), '.').' '.__('app.common.hr');
        }

        return ['minutes' => $mins, 'label' => $label];
    });

    // Built as plain PHP arrays (not inline inside @json(...)) because
    // @json()'s compiler does a naive explode(',', ...) on its argument —
    // any top-level comma in an inline array literal (i.e. more than one
    // entry) silently truncates it to everything before the first comma.
    $roomStateLabels = [
        'free' => __('app.booking.rooms.state_free'),
        'partial' => __('app.booking.rooms.state_partial'),
        'unavailable' => __('app.booking.rooms.state_unavailable'),
    ];
    $paymentStatusLabels = [
        'paid' => __('app.booking.payment.status_paid'),
        'partial' => __('app.booking.payment.status_partial'),
        'unpaid' => __('app.booking.payment.status_unpaid'),
    ];
@endphp
<div class="ls-booking-layout">
    <form method="POST" action="{{ $actionUrl }}" class="ls-booking-form" id="booking-form">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- 1. Customer — the operator's starting point, styled as an actual selection, not a form field. --}}
        <div class="ls-card">
            <div class="ls-card-body">
                <h2 class="ls-section-label">{{ __('app.booking.customer') }}</h2>
                <p class="ls-section-hint">{{ __('app.booking.customer_hint') }}</p>
                <div class="relative">
                    @include('partials.member-picker', [
                        'label' => __('app.booking.customer'),
                        'selectedId' => $initialUserId,
                        'selectedName' => $isEdit ? $booking->hotspotUser->name : null,
                        'selectedPhone' => $isEdit ? $booking->hotspotUser->phone : null,
                        'hideLabel' => true,
                        'searchIcon' => true,
                    ])
                    @error('hotspot_user_id') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- 2. Reservation — light section, no card border. --}}
        <section class="ls-plain-section">
            <h2 class="ls-section-label">{{ __('app.booking.summary.title') }}</h2>
            <div class="ls-reservation-row">
                <div class="ls-field ls-reservation-date">
                    <label class="ls-label" for="booking_date">{{ __('app.booking.date') }}</label>
                    <input type="date" name="booking_date" id="booking_date" class="ls-input"
                           @if (! $isEdit) min="{{ now()->format('Y-m-d') }}" @endif
                           value="{{ $initialDate }}" required>
                    @error('booking_date') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
                </div>

                <div class="ls-field ls-reservation-duration">
                    <span class="ls-label">{{ __('app.booking.duration') }}</span>
                    <div class="ls-chips ls-chips-scroll" id="duration-chips" role="group" aria-label="{{ __('app.booking.duration') }}">
                        @foreach ($durationOptions as $opt)
                            <button type="button" class="ls-chip" data-duration-chip data-minutes="{{ $opt['minutes'] }}">{{ $opt['label'] }}</button>
                        @endforeach
                        <button type="button" class="ls-chip" data-duration-chip data-minutes="custom">{{ __('app.booking.custom') }}</button>
                    </div>
                </div>

                <div class="ls-field ls-reservation-start">
                    <label class="ls-label" id="start-stepper-label">{{ __('app.booking.stepper.start') }}</label>
                    <x-ui.time-stepper name="start_time" :value="$initialStart" :values="array_keys($timeSlots)"
                        :placeholder="__('app.common.select').' '.__('app.booking.start_time')" :aria-label="__('app.booking.start_time')" />
                    <p class="ls-hint" id="ends-at-hint"></p>
                    @error('start_time') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="ls-field" id="custom-end-field" style="margin-top: var(--space-3); display: none;">
                <label class="ls-label">{{ __('app.booking.end_time') }}</label>
                <x-ui.time-picker name="end_time" :value="$initialEnd" :values="array_keys($timeSlots)"
                    :placeholder="__('app.common.select').' '.__('app.booking.end_time')" :aria-label="__('app.booking.end_time')"
                    :disabled-up-to="$initialStart !== '' ? $initialStart : null" />
                @error('end_time') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
            </div>

            <p class="ls-reservation-result" id="reservation-result"></p>
        </section>

        {{-- 3. Room --}}
        <div class="ls-card">
            <div class="ls-card-body">
                <h2 class="ls-section-label">{{ __('app.booking.room') }}</h2>
                <div class="ls-room-grid" id="room-grid" role="radiogroup" aria-label="{{ __('app.booking.room') }}">
                    @foreach ($rooms as $r)
                        @include('bookings._room-card', ['room' => $r, 'selected' => $initialRoomId === (string) $r->id])
                    @endforeach
                </div>
                @error('room_id') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- 4. Payment --}}
        @include('bookings._payment', ['amountPaid' => $initialAmountPaid])

        <details class="ls-plain-section ls-notes-details">
            <summary class="ls-section-label">{{ __('app.placeholder.notes_optional') }}</summary>
            <textarea name="notes" rows="2" class="ls-textarea" placeholder="{{ __('app.placeholder.special_requests') }}">{{ $initialNotes }}</textarea>
            @error('notes') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
        </details>
    </form>

    {{-- 5. Confirmation --}}
    @include('bookings._confirm-summary', ['isEdit' => $isEdit])
</div>

<script>
(function () {
    const form          = document.getElementById('booking-form');
    const dateInput     = document.getElementById('booking_date');
    const startHidden   = document.getElementById('start_time');
    const endHidden     = document.getElementById('end_time');
    const startTrigger  = document.getElementById('start_time-trigger');
    const endTrigger    = document.getElementById('end_time-trigger');
    const startText     = document.getElementById('start_time-trigger-text');
    const endText       = document.getElementById('end_time-trigger-text');
    const startGrid     = document.getElementById('start_time-grid');
    const endGrid       = document.getElementById('end_time-grid');
    const startOptions  = JSON.parse(document.getElementById('start_time-pop').dataset.options);
    const endOptions    = JSON.parse(document.getElementById('end_time-pop').dataset.options);
    const startValues   = Object.keys(startOptions);
    const customEndField = document.getElementById('custom-end-field');
    const endsAtHint    = document.getElementById('ends-at-hint');
    const reservationResult = document.getElementById('reservation-result');
    const durationChips = [...document.querySelectorAll('[data-duration-chip]')];
    const roomGrid      = document.getElementById('room-grid');
    const roomCards     = [...document.querySelectorAll('[data-room-card]')];
    const amountInput   = document.getElementById('f-amount_paid');
    const payPresets    = [...document.querySelectorAll('[data-pay-preset]')];
    const payCustomBtn  = document.getElementById('pay-custom-focus');
    const payStatusText = document.getElementById('pay-status-text');
    const paymentSection = document.getElementById('payment-section');
    const endPlaceholder = @json(__('app.common.select').' '.__('app.booking.end_time'));
    const roomStateLabels = @json($roomStateLabels);
    const paymentStatusLabels = @json($paymentStatusLabels);
    const ctaTemplate = @json($isEdit ? __('app.booking.confirm.cta_edit') : __('app.booking.confirm.cta'));
    const toHours = @json(__('app.common.hours'));
    const endsAtTemplate = @json(__('app.booking.stepper.ends_at'));
    const arrowSep = @json(' → ');

    // Selected duration, in minutes — null once the user picks an explicit
    // End time directly (Custom), so Start-stepper nudges stop silently
    // recomputing an End the user chose on purpose.
    let durationMinutes = null;
    let lastRoomOptions = [];

    function addMinutes(hm, mins) {
        const [h, m] = hm.split(':').map(Number);
        const total = h * 60 + m + mins;
        return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
    }

    function diffMinutes(startHm, endHm) {
        const [sh, sm] = startHm.split(':').map(Number);
        const [eh, em] = endHm.split(':').map(Number);
        return (eh * 60 + em) - (sh * 60 + sm);
    }

    function setChecked(grid, value) {
        grid.querySelectorAll('.ls-time-cell[role="menuitemradio"]').forEach(cell => {
            cell.setAttribute('aria-checked', cell.dataset.value === value ? 'true' : 'false');
        });
    }

    function rebuildEndGrid(startValue) {
        const currentEnd = endHidden.value;
        const keepEnd = currentEnd !== '' && (!startValue || currentEnd > startValue);
        endGrid.innerHTML = Object.entries(endOptions).map(([value, label]) => {
            if (startValue && value <= startValue) {
                return `<span class="ls-time-cell is-disabled" aria-hidden="true">${label}</span>`;
            }
            const checked = keepEnd && value === currentEnd ? 'true' : 'false';
            return `<button type="button" role="menuitemradio" aria-checked="${checked}" class="ls-time-cell" data-value="${value}">${label}</button>`;
        }).join('');
        if (!keepEnd && currentEnd !== '') {
            endHidden.value = '';
            endText.textContent = endPlaceholder;
            endText.classList.add('is-placeholder');
        }
    }

    function setEnd(value) {
        endHidden.value = value;
        endText.textContent = endOptions[value] ?? value;
        endText.classList.remove('is-placeholder');
        rebuildEndGrid(startHidden.value);
        setChecked(endGrid, value);
    }

    function refreshStepperState() {
        const idx = startValues.indexOf(startHidden.value);
        document.querySelectorAll('[data-stepper-for="start_time"]').forEach(btn => {
            const dir = parseInt(btn.dataset.stepperDir, 10);
            btn.disabled = dir < 0 ? idx <= 0 : (idx === -1 || idx >= startValues.length - 1);
        });
    }

    function refreshDurationChips() {
        const startValue = startHidden.value;
        const endValue = endHidden.value;
        durationChips.forEach(chip => {
            chip.classList.remove('is-active');
            if (chip.dataset.minutes === 'custom') {
                chip.classList.toggle('is-active', durationMinutes === null && !!endValue);
                chip.disabled = !startValue;
                return;
            }
            if (!startValue) { chip.disabled = true; return; }
            const mins = parseInt(chip.dataset.minutes, 10);
            const computedEnd = addMinutes(startValue, mins);
            const valid = Object.prototype.hasOwnProperty.call(endOptions, computedEnd);
            chip.disabled = !valid;
            if (valid && computedEnd === endValue) chip.classList.add('is-active');
        });
    }

    function refreshReservationReadouts() {
        const start = startHidden.value, end = endHidden.value;
        endsAtHint.textContent = end ? endsAtTemplate.replace(':time', endOptions[end] ?? end) : '';
        reservationResult.textContent = (start && end)
            ? `${startOptions[start] ?? start}${arrowSep}${endOptions[end] ?? end} · ${durationLabel(diffMinutes(start, end))}`
            : '';
    }

    function applyDuration(mins) {
        if (!startHidden.value) return;
        const computedEnd = addMinutes(startHidden.value, mins);
        if (!Object.prototype.hasOwnProperty.call(endOptions, computedEnd)) return;
        durationMinutes = mins;
        customEndField.style.display = 'none';
        setEnd(computedEnd);
        refreshDurationChips();
        refreshReservationReadouts();
        fetchRoomOptions();
    }

    function selectStart(value) {
        startHidden.value = value;
        startText.textContent = startOptions[value] ?? value;
        startText.classList.remove('is-placeholder');
        setChecked(startGrid, value);
        if (startTrigger.getAttribute('aria-expanded') === 'true') startTrigger.click();
        refreshStepperState();

        if (durationMinutes !== null) {
            applyDuration(durationMinutes);
            return;
        }

        rebuildEndGrid(value);
        refreshDurationChips();
        refreshReservationReadouts();
        fetchRoomOptions();
    }

    startGrid.addEventListener('click', (e) => {
        const btn = e.target.closest('.ls-time-cell');
        if (!btn || btn.tagName !== 'BUTTON') return;
        selectStart(btn.dataset.value);
    });

    endGrid.addEventListener('click', (e) => {
        const btn = e.target.closest('.ls-time-cell');
        if (!btn || btn.tagName !== 'BUTTON') return;
        durationMinutes = null; // an explicit End breaks the duration-follows-start link until a chip is picked again
        setEnd(btn.dataset.value);
        refreshDurationChips();
        refreshReservationReadouts();
        fetchRoomOptions();
        if (endTrigger.getAttribute('aria-expanded') === 'true') endTrigger.click();
    });

    document.querySelectorAll('[data-stepper-dir]').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = startValues.indexOf(startHidden.value);
            const dir = parseInt(btn.dataset.stepperDir, 10);
            const nextIdx = (idx === -1 ? 0 : idx) + dir;
            if (nextIdx < 0 || nextIdx >= startValues.length) return;
            selectStart(startValues[nextIdx]);
        });
    });

    durationChips.forEach(chip => {
        chip.addEventListener('click', () => {
            if (chip.disabled) return;
            if (chip.dataset.minutes === 'custom') {
                durationMinutes = null;
                customEndField.style.display = '';
                refreshDurationChips();
                endTrigger.click();
                return;
            }
            applyDuration(parseInt(chip.dataset.minutes, 10));
        });
    });

    // --- Room cards ---

    function selectedRoomId() {
        const checked = roomGrid.querySelector('input[name="room_id"]:checked');
        return checked ? checked.value : null;
    }

    function applyRoomOption(card, opt, durationText) {
        if (card.dataset.shared === 'true') return; // shared rooms keep their static "Open Session" note
        const priceEl = card.querySelector('[data-room-price] .ls-room-card-price-value');
        const durationEl = card.querySelector('[data-room-price] .ls-room-card-price-duration');
        const stateTextEl = card.querySelector('[data-room-state-text]');
        const checkEl = card.querySelector('[data-room-check]');
        priceEl.textContent = opt ? opt.total_price_display : ' ';
        durationEl.textContent = opt && durationText ? durationText : '';
        card.classList.toggle('is-unavailable', !!opt && opt.state === 'unavailable');
        if (opt) {
            card.dataset.state = opt.state;
            stateTextEl.textContent = roomStateLabels[opt.state] || '';
            if (checkEl) checkEl.hidden = opt.state !== 'free';
        }
    }

    function updateRoomSelectedClasses() {
        const current = selectedRoomId();
        roomCards.forEach(card => card.classList.toggle('is-selected', card.dataset.roomId === current));
    }

    roomGrid.addEventListener('change', () => {
        updateRoomSelectedClasses();
        updateSummaryAndPayment();
    });
    updateRoomSelectedClasses();

    // --- Room availability/pricing (replaces the old checkAvailability() call) ---

    let roomOptionsToken = 0;

    function fetchRoomOptions() {
        const date = dateInput.value;
        const start = startHidden.value;
        const end = endHidden.value;
        if (!date || !start || !end) return;

        const params = new URLSearchParams({ booking_date: date, start_time: start, end_time: end });
        @if ($isEdit)
            params.set('booking_id', '{{ $booking->id }}');
        @endif

        const token = ++roomOptionsToken;
        fetch(`/bookings/room-options?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (token !== roomOptionsToken) return; // a newer request already landed
                lastRoomOptions = data.rooms || [];
                const durationText = (start && end) ? durationLabel(diffMinutes(start, end)) : '';
                lastRoomOptions.forEach(opt => {
                    const card = roomGrid.querySelector(`[data-room-card][data-room-id="${opt.id}"]`);
                    if (card) applyRoomOption(card, opt, durationText);
                });
                updateSummaryAndPayment();
            })
            .catch(() => {});
    }

    function currentRoomOption() {
        const id = selectedRoomId();
        return id ? lastRoomOptions.find(o => String(o.id) === String(id)) : null;
    }

    // --- Payment ---

    function paymentStatusFor(paid, total) {
        if (total <= 0) return paid > 0 ? 'paid' : 'unpaid';
        if (paid >= total) return 'paid';
        return paid > 0 ? 'partial' : 'unpaid';
    }

    function setBadge(el, tone, text) {
        el.textContent = text;
        el.className = el.className.replace(/\bls-badge--\S+/, '').trim();
        el.classList.add('ls-badge--' + tone);
    }

    function setStatusText(el, tone, text) {
        el.textContent = text;
        el.className = el.className.replace(/\bls-status--\S+/, '').trim();
        el.classList.add('ls-status--' + tone);
    }

    const toneFor = { paid: 'ok', partial: 'warn', unpaid: 'neutral' };

    function updateSummaryAndPayment() {
        const opt = currentRoomOption();
        const total = opt ? opt.total_price : 0;
        const isShared = opt ? opt.is_shared : false;

        paymentSection.style.display = (opt && !isShared) ? '' : 'none';
        document.getElementById('pay-shared-note').hidden = !(opt && isShared);

        let paid = 0;
        if (opt && !isShared) {
            paid = Math.max(0, Math.min(parseFloat(amountInput.value || '0') || 0, total));
        }
        const remaining = Math.max(0, total - paid);
        const status = opt ? paymentStatusFor(paid, total) : 'unpaid';

        document.getElementById('pay-total').textContent = opt ? opt.total_price_display : '—';
        document.getElementById('pay-remaining').textContent = opt ? formatMoney(remaining) : '—';
        if (payStatusText) setStatusText(payStatusText, toneFor[status], opt ? paymentStatusLabels[status] : '—');

        payPresets.forEach(btn => {
            btn.disabled = !opt || isShared;
            btn.classList.toggle('is-active', !!opt && Math.abs(parseFloat(amountInput.value || '0') - Math.round(total * parseFloat(btn.dataset.payPreset) * 100) / 100) < 0.005);
        });
        if (payCustomBtn) payCustomBtn.disabled = !opt || isShared;

        // --- Confirmation summary (the one place totals/paid/remaining/status are spelled out) ---
        const customerDisplay = document.getElementById('selected-user-display');
        document.getElementById('confirm-customer').textContent = (customerDisplay && !customerDisplay.classList.contains('hidden'))
            ? document.getElementById('selected-user-name').textContent : '—';
        document.getElementById('confirm-room').textContent = opt ? roomNameFor(opt.id) : '—';
        document.getElementById('confirm-datetime').textContent = dateInput.value
            ? `${formatDate(dateInput.value)}${(startHidden.value && endHidden.value) ? ' · ' + startOptions[startHidden.value] + arrowSep + endOptions[endHidden.value] : ''}`
            : '—';
        document.getElementById('confirm-duration').textContent = (startHidden.value && endHidden.value)
            ? durationLabel(diffMinutes(startHidden.value, endHidden.value)) : '—';
        document.getElementById('confirm-total').textContent = opt ? opt.total_price_display : '—';
        document.getElementById('confirm-paid').textContent = opt ? formatMoney(paid) : '—';
        document.getElementById('confirm-remaining').textContent = opt ? formatMoney(remaining) : '—';
        setBadge(document.getElementById('confirm-status-badge'), toneFor[status], paymentStatusLabels[status]);

        const cta = document.getElementById('confirm-cta');
        cta.textContent = ctaTemplate.replace(':total', opt ? opt.total_price_display : '—');
    }

    function roomNameFor(id) {
        const card = roomGrid.querySelector(`[data-room-card][data-room-id="${id}"] .ls-room-card-name`);
        return card ? card.textContent : '—';
    }

    function formatMoney(n) {
        const s = (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return document.documentElement.dir === 'rtl' ? `${s} ج.م` : `EGP ${s}`;
    }

    function formatDate(ymd) {
        const d = new Date(ymd + 'T00:00:00');
        return d.toLocaleDateString(document.documentElement.lang === 'ar' ? 'ar-EG' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function durationLabel(mins) {
        const h = Math.floor(mins / 60), m = mins % 60;
        const parts = [];
        if (h) parts.push(h + '{{ __('app.ui.unit_h') }}');
        if (m) parts.push(m + '{{ __('app.ui.unit_m') }}');
        return parts.join(' ') || ('0' + toHours);
    }

    amountInput.addEventListener('input', () => {
        const opt = currentRoomOption();
        if (opt) {
            const clamped = Math.max(0, Math.min(parseFloat(amountInput.value || '0') || 0, opt.total_price));
            if (String(clamped) !== amountInput.value) amountInput.value = clamped;
        }
        updateSummaryAndPayment();
    });

    payPresets.forEach(btn => {
        btn.addEventListener('click', () => {
            const opt = currentRoomOption();
            if (!opt) return;
            amountInput.value = Math.round(opt.total_price * parseFloat(btn.dataset.payPreset) * 100) / 100;
            updateSummaryAndPayment();
        });
    });

    if (payCustomBtn) {
        payCustomBtn.addEventListener('click', () => {
            if (payCustomBtn.disabled) return;
            amountInput.focus();
            amountInput.select();
        });
    }

    dateInput.addEventListener('change', () => {
        updateSummaryAndPayment();
        fetchRoomOptions();
    });

    // member-picker.blade.php defines these globals in its own inline script,
    // which runs before this one (it's included earlier in the same form) —
    // wrapped rather than edited, so the picker's own logic stays untouched.
    const baseSelectUser = window.selectUser;
    window.selectUser = function (...args) {
        baseSelectUser(...args);
        updateSummaryAndPayment();
    };
    const baseClearUserSelection = window.clearUserSelection;
    window.clearUserSelection = function (...args) {
        baseClearUserSelection(...args);
        updateSummaryAndPayment();
    };

    // --- Initial state ---
    refreshStepperState();
    refreshDurationChips();
    refreshReservationReadouts();
    if (startHidden.value && endHidden.value) {
        durationMinutes = diffMinutes(startHidden.value, endHidden.value);
        // Only treat it as a "known" duration chip if it actually matches one;
        // otherwise this is a Custom-picked range — show the End picker
        // directly rather than a chip that can't represent it.
        if (!durationChips.some(c => c.dataset.minutes === String(durationMinutes))) {
            durationMinutes = null;
            customEndField.style.display = '';
        }
    }
    if (dateInput.value && startHidden.value && endHidden.value) {
        fetchRoomOptions();
    } else {
        updateSummaryAndPayment();
    }
})();
</script>
