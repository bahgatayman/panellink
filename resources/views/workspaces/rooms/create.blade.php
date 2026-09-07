@extends('layouts.app')

@section('page-title', __('app.workspace.add_room'))

@section('content')
    <div class="max-w-lg mx-auto">
        <nav class="text-sm text-gray-500 mb-4">
            <a href="{{ route('workspaces.index') }}" class="text-blue-600 hover:text-blue-800">{{ __('app.section.workspaces') }}</a>
            <span class="mx-2">/</span>
            <a href="{{ route('workspaces.show', $workspace) }}" class="text-blue-600 hover:text-blue-800">{{ $workspace->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ __('app.workspace.add_room') }}</span>
        </nav>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-6">{{ __('app.workspace.add_room') }}</h1>

            <form method="POST" action="{{ route('rooms.store', $workspace) }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.workspace.room_name') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.workspace.type') }} <span class="text-red-500">*</span></label>
                        <select name="type" id="room_type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">{{ __('app.placeholder.select_type') }}</option>
                            @foreach($roomTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.workspace.capacity') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="capacity" min="1" max="999"
                               value="{{ old('capacity', 1) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('capacity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.workspace.price_per_hour') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">ج.م</span>
                            <input type="number" name="price_per_hour" id="price_per_hour" value="{{ old('price_per_hour', '0.00') }}" step="0.01" min="0" required
                                class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        @error('price_per_hour') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="billing-unit-field" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.workspace.billing_unit') }}</label>
                        <div class="space-y-2">
                            @foreach(['minute' => 1, 'half_hour' => 30, 'hour' => 60] as $unitKey => $unitMinutes)
                                <label class="flex items-start gap-2 border border-gray-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="billing_unit" value="{{ $unitKey }}"
                                        class="billing-unit-radio mt-0.5 text-blue-600 focus:ring-blue-500"
                                        {{ old('billing_unit', 'minute') === $unitKey ? 'checked' : '' }}>
                                    <span class="text-sm">
                                        <span class="block font-medium text-gray-800">{{ __('app.billing_unit.'.$unitKey) }}</span>
                                        <span class="block text-xs text-gray-500 billing-example" data-unit-minutes="{{ $unitMinutes }}">&nbsp;</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ __('app.workspace.billing_unit_block_hint') }}</p>
                        @error('billing_unit') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.workspace.description') }}</label>
                        <textarea name="description" rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description') }}</textarea>
                        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                        {{ __('app.btn.add_room') }}
                    </button>
                    <a href="{{ route('workspaces.show', $workspace) }}"
                        class="text-sm text-gray-600 hover:text-gray-800">{{ __('app.common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const typeSelect = document.getElementById('room_type');
        const priceInput = document.getElementById('price_per_hour');
        const billingField = document.getElementById('billing-unit-field');
        const exampleTemplate = @json(__('app.workspace.billing_example'));
        const EXAMPLE_MINUTES = 10;

        function toggleBillingField() {
            billingField.classList.toggle('hidden', typeSelect.value !== 'shared');
        }

        // Charging every started block, rounded up — mirrors
        // SharedSessionBillingService::calculate() exactly, just for a fixed
        // 10-minute example so the owner sees the real consequence of each
        // choice before saving.
        function updateExamples() {
            const price = parseFloat(priceInput.value) || 100;
            document.querySelectorAll('.billing-example').forEach(el => {
                const unitMinutes = parseInt(el.dataset.unitMinutes, 10);
                const blocks = Math.ceil(EXAMPLE_MINUTES / unitMinutes);
                const total = (blocks * unitMinutes / 60) * price;
                el.textContent = exampleTemplate.replace(':minutes', EXAMPLE_MINUTES).replace(':price', total.toFixed(2));
            });
        }

        typeSelect.addEventListener('change', toggleBillingField);
        priceInput.addEventListener('input', updateExamples);

        toggleBillingField();
        updateExamples();
    })();
    </script>
@endsection
