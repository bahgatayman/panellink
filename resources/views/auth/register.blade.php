@extends('layouts.auth')

@section('title', __('app.auth.register'))
@section('heading', __('app.auth.create_your_account'))
@section('subheading', __('app.auth.coworking_management'))
@section('formWidth', 'max-w-xl')

@section('content')
    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-red-50 border border-red-100 px-4 py-3 space-y-1">
            @foreach ($errors->all() as $error)
                <p class="text-sm text-red-600 flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $error }}</span>
                </p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/register" class="space-y-6">
        @csrf

        <div class="space-y-4">
            <p class="group-lbl">{{ __('app.auth.account_information') }}</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="lbl">{{ __('app.auth.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="field">
                </div>
                <div>
                    <label for="business_name" class="lbl">{{ __('app.auth.business_name') }}</label>
                    <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" required class="field">
                </div>
            </div>
            <div>
                <label for="email" class="lbl">{{ __('app.auth.email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="you@example.com" class="field">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="lbl">{{ __('app.auth.password') }}</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required minlength="8" class="field pe-10">
                        <button type="button" class="pw-toggle absolute inset-y-0 end-0 flex items-center pe-3 text-surface-400 hover:text-surface-600" tabindex="-1" aria-label="Show password">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="password_confirmation" class="lbl">{{ __('app.auth.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required class="field">
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <p class="group-lbl">{{ __('app.auth.mikrotik_connection') }}</p>
            <div>
                <label for="mikrotik_host" class="lbl">{{ __('app.auth.router_ip') }}</label>
                <input type="text" name="mikrotik_host" id="mikrotik_host" value="{{ old('mikrotik_host') }}" required placeholder="192.168.88.1" class="field">
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div>
                    <label for="mikrotik_port" class="lbl">{{ __('app.auth.api_port') }}</label>
                    <input type="number" name="mikrotik_port" id="mikrotik_port" value="{{ old('mikrotik_port', 8728) }}" required class="field">
                </div>
                <div>
                    <label for="mikrotik_username" class="lbl">{{ __('app.label.mikrotik_username') }}</label>
                    <input type="text" name="mikrotik_username" id="mikrotik_username" value="{{ old('mikrotik_username') }}" required class="field">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label for="mikrotik_password" class="lbl">{{ __('app.label.mikrotik_password') }}</label>
                    <input type="password" name="mikrotik_password" id="mikrotik_password" required class="field">
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary">
            {{ __('app.auth.create_account') }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </button>
    </form>

    <p class="text-center text-sm text-surface-500 mt-7">
        {{ __('app.auth.already_have_account') }}
        <a href="/login" class="text-indigo-600 font-semibold hover:text-indigo-700">{{ __('app.auth.login') }}</a>
    </p>
@endsection
