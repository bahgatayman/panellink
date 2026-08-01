@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ __('app.section.settings') }}</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if ($owner->hasFeature('hotspot'))
        <div class="bg-white rounded-lg shadow p-6 mb-6 max-w-2xl">
            <h2 class="text-lg font-semibold text-gray-700 mb-1">{{ __('app.profile.mikrotik_connection') }}</h2>
            <p class="text-sm text-gray-500 mb-5">{{ __('app.msg.check_mikrotik_settings') }}</p>

            @error('mikrotik_host') <p class="text-sm text-red-600 mb-3">{{ $message }}</p> @enderror

            <form method="POST" action="/settings" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_host') }}</label>
                        <input type="text" name="mikrotik_host" value="{{ old('mikrotik_host', $owner->mikrotik_host) }}" required
                               placeholder="{{ __('app.placeholder.router_ip') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_port') }}</label>
                        <input type="number" name="mikrotik_port" value="{{ old('mikrotik_port', $owner->mikrotik_port ?? 8728) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_username') }}</label>
                        <input type="text" name="mikrotik_username" value="{{ old('mikrotik_username', $owner->mikrotik_username) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_password') }}</label>
                        <input type="password" name="mikrotik_password" placeholder="••••••••"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <p class="text-xs text-gray-400 mt-1">{{ __('app.settings.password_keep_hint') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition text-sm font-medium">
                        {{ __('app.common.save') }}
                    </button>
                </div>
            </form>

            @if ($owner->hasRouterConfigured())
                <form method="POST" action="/settings/test-connection" class="mt-3 pt-4 border-t border-gray-100">
                    @csrf
                    <button type="submit" class="bg-white border border-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-50 transition text-sm font-medium">
                        {{ __('app.btn.test_connection') }}
                    </button>
                </form>
            @endif
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 max-w-2xl text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            </div>
            <p class="text-sm text-gray-500">{{ __('app.settings.no_settings') }}</p>
        </div>
    @endif
@endsection
