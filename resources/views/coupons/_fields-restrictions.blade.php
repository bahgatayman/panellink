{{-- $coupon --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.starts_at') }}</label>
        <input type="date" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->toDateString()) }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.expires_at') }}</label>
        <input type="date" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->toDateString()) }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.usage_limit') }}</label>
        <input type="number" min="0" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="{{ __('app.coupons.no_limit') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.per_customer_limit') }}</label>
        <input type="number" min="0" name="per_customer_limit" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}" placeholder="{{ __('app.coupons.no_limit') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.minimum_spend') }}</label>
        <input type="number" step="0.01" min="0" name="minimum_spend" value="{{ old('minimum_spend', $coupon->minimum_spend) }}" placeholder="ج.م 0.00"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
</div>
