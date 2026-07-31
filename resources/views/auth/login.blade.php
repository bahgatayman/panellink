@extends('layouts.auth')

@section('title', __('app.auth.login'))
@section('heading', __('app.auth.welcome_back'))
@section('subheading', __('app.auth.login_subtitle'))

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3">
            <p class="text-sm text-emerald-700 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('status') }}</span>
            </p>
        </div>
    @endif

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

    <form method="POST" action="/login" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="lbl">{{ __('app.auth.email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                   placeholder="you@example.com" class="field">
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="lbl mb-0">{{ __('app.auth.password') }}</label>
                <a href="/forgot-password" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">{{ __('app.auth.forgot_password') }}</a>
            </div>
            <div class="relative">
                <input type="password" name="password" id="password" required class="field pe-10">
                <button type="button" class="pw-toggle absolute inset-y-0 end-0 flex items-center pe-3 text-surface-400 hover:text-surface-600" tabindex="-1" aria-label="Show password">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                </button>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-surface-600 select-none cursor-pointer">
            <input type="checkbox" name="remember" value="1" class="rounded border-surface-300 text-indigo-600 focus:ring-indigo-500/40">
            {{ __('app.auth.remember_me') }}
        </label>

        <button type="submit" class="btn-primary">
            {{ __('app.auth.sign_in') }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </button>
    </form>

    <p class="text-center text-sm text-surface-500 mt-7">
        {{ __('app.auth.dont_have_account') }}
        <a href="/register" class="text-indigo-600 font-semibold hover:text-indigo-700">{{ __('app.auth.register') }}</a>
    </p>
@endsection
