@extends('layouts.admin')

@section('page-title', __('app.plan.add_plan'))

@section('content')
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.plan.add_plan') }}</h1>

        <form method="POST" action="/admin/plans" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:p-8 space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required id="plan-name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.slug') }}</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" required id="plan-slug"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('slug') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.max_members') }}</label>
                    <input type="number" name="max_members" value="{{ old('max_members') }}" required min="1"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('max_members') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.max_workspaces') }}</label>
                    <input type="number" name="max_workspaces" value="{{ old('max_workspaces', 0) }}" required min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">{{ __('app.plan.unlimited_hint') }}</p>
                    @error('max_workspaces') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.max_rooms') }}</label>
                    <input type="number" name="max_rooms" value="{{ old('max_rooms', 0) }}" required min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">{{ __('app.plan.unlimited_hint') }}</p>
                    @error('max_rooms') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.max_products') }}</label>
                    <input type="number" name="max_products" value="{{ old('max_products', 0) }}" required min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">{{ __('app.plan.unlimited_hint') }}</p>
                    @error('max_products') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.price_per_month') }}</label>
                    <input type="number" name="price_per_month" value="{{ old('price_per_month') }}" required min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('price_per_month') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.sort_order') }}</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    @error('sort_order') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.plan.features') }}</label>
                <p class="text-xs text-gray-400 mb-3">{{ __('app.plan.features_hint') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach($features as $feature)
                        <label class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition">
                            <input type="checkbox" name="features[]" value="{{ $feature->key }}" class="text-brand-600 focus:ring-brand-500"
                                   {{ collect(old('features'))->contains($feature->key) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-800">{{ $feature->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('features') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="bg-red-600 text-white px-6 py-2.5 rounded-lg hover:bg-red-700 transition font-medium shadow-sm">
                    {{ __('app.plan.create_plan') }}
                </button>
                <a href="/admin/plans" class="text-gray-600 hover:text-gray-800 text-sm font-medium">{{ __('app.common.cancel') }}</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('plan-name').addEventListener('input', function() {
        const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        document.getElementById('plan-slug').value = slug;
    });
    </script>
@endsection
