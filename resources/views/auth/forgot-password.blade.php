@extends('layouts.auth')

@section('title', __('app.auth.forgot_password'))
@section('heading', __('app.auth.forgot_password'))
@section('subheading', __('app.auth.forgot_password_hint'))

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

    <form method="POST" action="/forgot-password" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="lbl">{{ __('app.auth.email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                   placeholder="you@example.com" class="field">
        </div>
        <button type="submit" class="btn-primary">
            {{ __('app.auth.send_reset_link') }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </button>
    </form>

    <p class="text-center text-sm text-surface-500 mt-7">
        <a href="/login" class="text-indigo-600 font-semibold hover:text-indigo-700 inline-flex items-center gap-1.5">
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('app.auth.back_to_login') }}
        </a>
    </p>
@endsection
