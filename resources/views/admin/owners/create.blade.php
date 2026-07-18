@extends('layouts.admin')

@section('page-title', __('app.admin.add_owner'))

@section('content')
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.admin.add_owner') }}</h1>

        <form method="POST" action="/admin/owners" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:p-8 space-y-6">
            @csrf

            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">{{ __('app.label.owner_info') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.common.name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.common.email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.business_name') }}</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('business_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.password') }}</label>
                        <input type="password" name="password" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">{{ __('app.auth.mikrotik_connection') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_host') }}</label>
                        <input type="text" name="mikrotik_host" value="{{ old('mikrotik_host') }}" required placeholder="{{ __('app.placeholder.router_ip') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('mikrotik_host') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_port') }}</label>
                        <input type="number" name="mikrotik_port" value="{{ old('mikrotik_port', 8728) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('mikrotik_port') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_username') }}</label>
                        <input type="text" name="mikrotik_username" value="{{ old('mikrotik_username') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('mikrotik_username') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_password') }}</label>
                        <input type="password" name="mikrotik_password" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('mikrotik_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">{{ __('app.label.subscription') }}</h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.common.plan') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        @php $plans = \App\Models\Plan::orderBy('sort_order')->get(); @endphp
                        @foreach($plans as $plan)
                        <label class="cursor-pointer">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}"
                                {{ old('plan_id', $plans->first()->id) == $plan->id ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="border-2 rounded-xl p-3 transition
                                peer-checked:border-red-500 peer-checked:bg-red-50
                                hover:border-gray-300">
                                <p class="font-semibold text-sm text-gray-900">{{ $plan->name }}</p>
                                <p class="text-xs text-gray-500">{{ $plan->max_members }} {{ __('app.plan.members') }}</p>
                                <p class="text-sm font-bold text-red-600 mt-1">{{ $plan->formattedPrice() }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('plan_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.table.th.months') }}</label>
                    <input type="number" name="months" value="{{ old('months', 1) }}" min="1" max="24" required
                           class="w-full sm:w-48 border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Owner will have access for this many months starting today.</p>
                    @error('months') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="bg-red-600 text-white px-6 py-2.5 rounded-lg hover:bg-red-700 transition font-medium shadow-sm">
                    {{ __('app.btn.create_owner') }}
                </button>
                <a href="/admin/owners" class="text-gray-600 hover:text-gray-800 text-sm font-medium">{{ __('app.common.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
