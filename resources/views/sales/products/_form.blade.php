<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.sales.name') }} <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.sales.type') }} <span class="text-red-500">*</span></label>
            @php $currentType = old('type', $product->type ?? 'product'); @endphp
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="product" @selected($currentType === 'product')>{{ __('app.sales.product') }}</option>
                <option value="service" @selected($currentType === 'service')>{{ __('app.sales.service') }}</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">{{ __('app.sales.type_hint') }}</p>
            @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.sales.price') }} (ج.م) <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price ?? '0.00') }}" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('price') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.sales.sku') }}</label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        @error('sku') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.sales.description') }}</label>
        <textarea name="description" rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <span class="text-sm text-gray-700">{{ __('app.sales.is_active') }}</span>
    </label>
</div>
