@extends('layouts.auth')

@section('title', __('app.auth.reset_password'))
@section('heading', __('app.auth.reset_password'))
@section('subheading', __('app.auth.reset_password_hint'))

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

    <form method="POST" action="/reset-password" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="lbl">{{ __('app.auth.email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required autofocus
                   class="field">
        </div>

        <div>
            <label for="password" class="lbl">{{ __('app.auth.new_password') }}</label>
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
            <div class="relative">
                <input type="password" name="password_confirmation" id="password_confirmation" required class="field pe-10">
                <button type="button" class="pw-toggle absolute inset-y-0 end-0 flex items-center pe-3 text-surface-400 hover:text-surface-600" tabindex="-1" aria-label="Show password">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary">
            {{ __('app.auth.reset_password') }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </button>
    </form>

    <p class="text-center text-sm text-surface-500 mt-7">
        <a href="/login" class="text-indigo-600 font-semibold hover:text-indigo-700 inline-flex items-center gap-1.5">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('app.auth.back_to_login') }}
        </a>
    </p>
@endsection
