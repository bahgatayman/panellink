{{--
    One selectable room card — compact by design: name + availability first
    (what the operator scans for), capacity/rate as a secondary line, then
    the price for the currently selected duration last. Static fields
    (name/type/capacity/rate) render server-side from $room; the live fields
    (price at the chosen duration, availability label) start as placeholders
    and are filled in by the page's script from GET /bookings/room-options —
    see data-room-price / data-room-state-text below, targeted by
    data-room-id.

    Shared rooms are rendered but not selectable here, exactly matching the
    room <select>'s existing `disabled` behavior for shared rooms on this
    same form — this card view doesn't change that, it only reshapes it.
--}}
@php
    $selected = $selected ?? false;
    $shared = $room->isShared();
@endphp
<label class="ls-room-card {{ $shared ? 'is-disabled' : '' }}" data-room-card data-room-id="{{ $room->id }}" data-shared="{{ $shared ? 'true' : 'false' }}">
    <input type="radio" name="room_id" value="{{ $room->id }}" class="ls-room-card-radio"
           {{ $selected && ! $shared ? 'checked' : '' }} {{ $shared ? 'disabled' : '' }} required>
    <div class="ls-room-card-top">
        <span class="ls-room-card-name ls-trunc">
            {{ $room->name }}
            <x-ui.icon name="check-circle" class="ls-room-card-check" data-room-check hidden />
        </span>
        <span class="ls-room-card-state" data-room-state>
            <span class="ls-dot"></span>
            <span data-room-state-text>{{ $shared ? __('app.session.open_session') : __('app.booking.rooms.loading') }}</span>
        </span>
    </div>
    <div class="ls-room-card-meta">
        <span class="ls-num">{{ __('app.booking.rooms.seats', ['count' => $room->capacity]) }}</span>
        <span aria-hidden="true">&middot;</span>
        <span class="ls-num">{{ $room->pricingSummary() }}</span>
    </div>
    <div class="ls-room-card-price" data-room-price>
        <span class="ls-room-card-price-duration"></span>
        <span class="ls-room-card-price-value ls-num">&nbsp;</span>
    </div>
</label>
