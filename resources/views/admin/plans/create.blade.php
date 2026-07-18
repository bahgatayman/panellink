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
