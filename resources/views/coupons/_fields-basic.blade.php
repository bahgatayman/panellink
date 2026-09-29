{{-- $coupon (Coupon, may be a fresh un-saved instance) --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.code') }}</label>
        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required maxlength="32"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-blue-500" dir="ltr">
        <p class="text-xs text-gray-400 mt-1">{{ __('app.coupons.code_hint') }}</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.discount_type') }}</label>
        <select name="discount_type" data-discount-type class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="percentage" {{ old('discount_type', $coupon->discount_type) === 'percentage' ? 'selected' : '' }}>{{ __('app.coupons.percentage') }}</option>
            <option value="fixed" {{ old('discount_type', $coupon->discount_type) === 'fixed' ? 'selected' : '' }}>{{ __('app.coupons.fixed') }}</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.coupons.value') }}</label>
        <div class="relative">
            <input type="number" step="0.01" min="0.01" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" required
                   data-discount-value class="w-full border border-gray-300 rounded-lg px-3 py-2 pe-12 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span data-discount-unit class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm text-gray-400 pointer-events-none"></span>
        </div>
    </div>
</div>

@once
    <script>
    (function () {
        function refreshUnit(scope) {
            var typeSelect = scope.querySelector('[data-discount-type]');
            var unit = scope.querySelector('[data-discount-unit]');
            if (!typeSelect || !unit) return;
            unit.textContent = typeSelect.value === 'fixed' ? 'ج.م' : '%';
        }
        function refreshAll() {
            document.querySelectorAll('[data-discount-type]').forEach(function (select) {
                refreshUnit(select.closest('form') || document);
            });
        }
        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-discount-type]')) refreshUnit(e.target.closest('form') || document);
        });
        document.addEventListener('DOMContentLoaded', refreshAll);
        if (document.readyState !== 'loading') refreshAll();
    })();
    </script>
@endonce
