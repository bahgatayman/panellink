{{-- $newCoupon (fresh Coupon instance), $roomGroups, $productGroups --}}
<x-ui.modal id="create-coupon" size="wide" :title="__('app.coupons.add')" :subtitle="__('app.coupons.subtitle')">
    <div id="coupon-preview" class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
        <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
            <x-ui.icon name="tag" class="w-4 h-4 text-white" />
        </div>
        <div class="min-w-0">
            <p class="font-mono font-semibold text-gray-900 truncate" dir="ltr" data-preview-code>{{ old('code') ?: __('app.coupons.code') }}</p>
            <p class="text-xs text-gray-500" data-preview-summary></p>
        </div>
    </div>

    <form id="create-coupon-form" method="POST" action="{{ route('coupons.store') }}" class="space-y-5 mt-5">
        @csrf
        <input type="hidden" name="is_active" value="1">

        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ __('app.coupons.section_basic') }}</p>
            @include('coupons._fields-basic', ['coupon' => $newCoupon])
        </div>

        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ __('app.coupons.section_applies_to') }}</p>
            @include('coupons._fields-applies-to', ['coupon' => $newCoupon, 'selectedRoomIds' => [], 'selectedProductIds' => []])
        </div>

        <details class="border-t border-gray-100 pt-4 group">
            <summary class="flex items-center gap-1.5 cursor-pointer text-sm font-medium text-gray-700 select-none list-none">
                <x-ui.icon name="chevron-right" class="w-4 h-4 text-gray-400 transition group-open:rotate-90" />
                {{ __('app.coupons.advanced_options') }}
            </summary>
            <div class="mt-4">
                @include('coupons._fields-restrictions', ['coupon' => $newCoupon])
            </div>
        </details>
    </form>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-slot:footer>
        <button type="button" data-ls-close class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
            {{ __('app.common.cancel') }}
        </button>
        <button type="submit" form="create-coupon-form" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            {{ __('app.common.save') }}
        </button>
    </x-slot:footer>
</x-ui.modal>

@if ($errors->any())
    <script>document.addEventListener('DOMContentLoaded', () => LS.open('create-coupon'));</script>
@endif

@php
    // Computed here, not inline inside @json(...): this Blade directive's
    // argument parser has previously mis-split on the commas/parens inside
    // an inline array literal containing nested __() calls — see the
    // identical fix applied elsewhere in this app. A plain variable avoids it.
    $couponPreviewI18n = [
        'rooms' => __('app.coupons.rooms'),
        'products' => __('app.coupons.products'),
        'both' => __('app.coupons.both'),
        'off' => __('app.coupons.checkout.discount'),
    ];
@endphp
<script>
(function () {
    var form = document.getElementById('create-coupon-form');
    if (!form) return;

    var i18n = @json($couponPreviewI18n);

    function refreshPreview() {
        var code = form.querySelector('[name="code"]').value.trim().toUpperCase();
        var type = form.querySelector('[name="discount_type"]').value;
        var value = parseFloat(form.querySelector('[name="discount_value"]').value || '0');
        var appliesTo = form.querySelector('[name="applies_to"]:checked');
        var scopeLabel = appliesTo ? i18n[appliesTo.value] : '';

        var amount = type === 'fixed' ? ('ج.م ' + (isNaN(value) ? '0' : value)) : ((isNaN(value) ? '0' : value) + '%');

        document.querySelector('[data-preview-code]').textContent = code || '{{ __('app.coupons.code') }}';
        document.querySelector('[data-preview-summary]').textContent = amount + ' ' + i18n.off + (scopeLabel ? ' · ' + scopeLabel : '');
    }

    form.addEventListener('input', refreshPreview);
    form.addEventListener('change', refreshPreview);
    refreshPreview();
})();
</script>
