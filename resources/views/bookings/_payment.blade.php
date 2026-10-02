{{--
    Deposit collection — compact by design: Total, presets, the amount field,
    Remaining. Paid/Status/Total in full are shown once, in the sticky
    confirmation panel (_confirm-summary.blade.php), not repeated here.
    Hidden entirely for shared rooms (billed at checkout, via a flow this
    form doesn't touch) or before a room is picked — see #payment-section's
    display toggle in the page script. The server (BookingController::
    store/update) re-validates and re-derives payment_status regardless of
    anything computed here.
--}}
@php $amountPaid = $amountPaid ?? ''; $memberPackageId = $memberPackageId ?? ''; @endphp
<section class="ls-plain-section" id="payment-section">
    <h2 class="ls-section-label">{{ __('app.booking.payment.title') }}</h2>

    {{--
        Hour packages: "Normal payment · Use hour package", shown only when the
        member has packages (filled from /bookings/package-options). A chosen
        package covers the whole booking — no deposit is taken.
    --}}
    <input type="hidden" name="member_package_id" id="f-member_package_id" value="{{ old('member_package_id', $memberPackageId) }}">
    <div class="ls-pkg-pay" id="pkg-pay" hidden>
        <div class="ls-inv-seg" role="radiogroup" aria-label="{{ __('app.packages.pay_title') }}">
            <label><input type="radio" name="pay_mode" value="normal" data-pay-mode @checked(! old('member_package_id', $memberPackageId))><span>{{ __('app.packages.pay_normal') }}</span></label>
            <label><input type="radio" name="pay_mode" value="package" data-pay-mode @checked((bool) old('member_package_id', $memberPackageId))><span>{{ __('app.packages.pay_package') }}</span></label>
        </div>
        <p class="ls-hint" id="pkg-needs" hidden></p>
        <div class="ls-pkg-options" id="pkg-options" role="radiogroup" aria-label="{{ __('app.packages.pick_package') }}" hidden></div>
        <div class="ls-pkg-covered" id="pkg-covered" hidden><x-ui.icon name="check-circle" /><span></span></div>
    </div>

    <div id="pay-deposit">
    <div class="ls-pay-total-row">
        <span>{{ __('app.booking.payment.total') }}</span>
        <span class="ls-num" id="pay-total">&mdash;</span>
    </div>

    <div class="ls-chips" id="pay-presets" role="group" aria-label="{{ __('app.booking.payment.title') }}">
        <button type="button" class="ls-chip" data-pay-preset="0">{{ __('app.booking.payment.no_deposit') }}</button>
        <button type="button" class="ls-chip" data-pay-preset="0.25">{{ __('app.booking.payment.percent', ['p' => 25]) }}</button>
        <button type="button" class="ls-chip" data-pay-preset="0.5">{{ __('app.booking.payment.percent', ['p' => 50]) }}</button>
        <button type="button" class="ls-chip" data-pay-preset="1">{{ __('app.booking.payment.full') }}</button>
        <button type="button" class="ls-chip" id="pay-custom-focus">{{ __('app.booking.custom') }}</button>
    </div>

    <x-ui.input name="amount_paid" type="number" :label="__('app.booking.payment.paid_now')"
                :value="old('amount_paid', $amountPaid)" min="0" step="0.01" inputmode="decimal" />

    <div class="ls-pay-remaining-row">
        <span>{{ __('app.booking.payment.remaining') }}</span>
        <span>
            <span class="ls-num" id="pay-remaining">&mdash;</span>
            <span class="ls-status" id="pay-status-text">&mdash;</span>
        </span>
    </div>
    </div>

    <p class="ls-hint" id="pay-shared-note" hidden>{{ __('app.booking.payment.shared_estimate_note') }}</p>
</section>
