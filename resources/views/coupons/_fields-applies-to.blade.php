{{--
    $coupon, $roomGroups / $productGroups (Collection<string label, Collection<Room|Product>>),
    $selectedRoomIds / $selectedProductIds (array<int>)
--}}
@php
    $appliesTo = old('applies_to', $coupon->applies_to);
    $roomScope = old('room_scope', empty($selectedRoomIds) ? 'all' : 'specific');
    $productScope = old('product_scope', empty($selectedProductIds) ? 'all' : 'specific');
@endphp

<div>
    <div class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
        @foreach (['rooms' => __('app.coupons.rooms'), 'products' => __('app.coupons.products'), 'both' => __('app.coupons.both')] as $value => $label)
            <label class="cursor-pointer">
                <input type="radio" name="applies_to" value="{{ $value }}" data-applies-to-input class="peer sr-only" {{ $appliesTo === $value ? 'checked' : '' }}>
                <span class="block px-3.5 py-1.5 rounded-md text-sm font-medium text-gray-600 transition peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-1">
                    {{ $label }}
                </span>
            </label>
        @endforeach
    </div>
</div>

<div data-scope-block="rooms" class="border-t border-gray-100 pt-4 mt-4">
    <p class="text-sm font-medium text-gray-700 mb-2">{{ __('app.coupons.rooms') }}</p>
    <div class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 mb-3">
        <label class="cursor-pointer">
            <input type="radio" name="room_scope" value="all" data-scope-input="rooms" class="peer sr-only" {{ $roomScope === 'all' ? 'checked' : '' }}>
            <span class="block px-3.5 py-1.5 rounded-md text-sm font-medium text-gray-600 transition peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-1">
                {{ __('app.coupons.all_rooms') }}
            </span>
        </label>
        <label class="cursor-pointer">
            <input type="radio" name="room_scope" value="specific" data-scope-input="rooms" class="peer sr-only" {{ $roomScope === 'specific' ? 'checked' : '' }}>
            <span class="block px-3.5 py-1.5 rounded-md text-sm font-medium text-gray-600 transition peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-1">
                {{ __('app.coupons.specific_rooms') }}
            </span>
        </label>
    </div>
    <div data-scope-items="rooms" class="{{ $roomScope === 'specific' ? '' : 'hidden' }}">
        @include('coupons._item-picker', ['groups' => $roomGroups, 'name' => 'room_ids', 'selected' => old('room_ids', $selectedRoomIds), 'labeller' => fn ($room) => $room->name])
    </div>
</div>

<div data-scope-block="products" class="border-t border-gray-100 pt-4 mt-4">
    <p class="text-sm font-medium text-gray-700 mb-2">{{ __('app.coupons.products') }}</p>
    <div class="inline-flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 mb-3">
        <label class="cursor-pointer">
            <input type="radio" name="product_scope" value="all" data-scope-input="products" class="peer sr-only" {{ $productScope === 'all' ? 'checked' : '' }}>
            <span class="block px-3.5 py-1.5 rounded-md text-sm font-medium text-gray-600 transition peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-1">
                {{ __('app.coupons.all_products') }}
            </span>
        </label>
        <label class="cursor-pointer">
            <input type="radio" name="product_scope" value="specific" data-scope-input="products" class="peer sr-only" {{ $productScope === 'specific' ? 'checked' : '' }}>
            <span class="block px-3.5 py-1.5 rounded-md text-sm font-medium text-gray-600 transition peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-1">
                {{ __('app.coupons.specific_products') }}
            </span>
        </label>
    </div>
    <div data-scope-items="products" class="{{ $productScope === 'specific' ? '' : 'hidden' }}">
        @include('coupons._item-picker', ['groups' => $productGroups, 'name' => 'product_ids', 'selected' => old('product_ids', $selectedProductIds), 'labeller' => fn ($product) => $product->name])
    </div>
</div>

@once
    <script>
    (function () {
        function refresh() {
            var appliesTo = document.querySelector('[data-applies-to-input]:checked');
            var value = appliesTo ? appliesTo.value : 'both';
            document.querySelectorAll('[data-scope-block]').forEach(function (block) {
                var type = block.getAttribute('data-scope-block');
                block.classList.toggle('hidden', value !== 'both' && value !== type);
            });
            ['rooms', 'products'].forEach(function (type) {
                var chosen = document.querySelector('[data-scope-input="' + type + '"]:checked');
                var items = document.querySelector('[data-scope-items="' + type + '"]');
                if (items) items.classList.toggle('hidden', !chosen || chosen.value !== 'specific');
            });
        }
        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-applies-to-input], [data-scope-input]')) refresh();
        });
        document.addEventListener('DOMContentLoaded', refresh);
        if (document.readyState !== 'loading') refresh();
    })();
    </script>
@endonce
