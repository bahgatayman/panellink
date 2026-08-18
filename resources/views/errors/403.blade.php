@extends('layouts.auth')

@section('title', __('app.error.403_title'))

@section('content')
    @php
        $isAuthed = auth('admin')->check() || auth('owner')->check();
        $homeUrl = auth('admin')->check() ? '/admin/dashboard' : (auth('owner')->check() ? '/dashboard' : '/login');
        $homeLabel = $isAuthed ? __('app.error.go_to_dashboard') : __('app.error.go_to_login');
    @endphp
    <div class="text-center">
        <span class="inline-block text-xs font-mono font-semibold tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full mb-5">
            {{ __('app.error.error_code', ['code' => '403']) }}
        </span>

        <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center">
            <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </div>

        <h1 class="text-xl font-bold text-surface-900 mb-2">{{ __('app.error.403_heading') }}</h1>
        <p class="text-sm text-surface-500 mb-8">{{ __('app.error.403_message') }}</p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ $homeUrl }}" class="w-full sm:w-auto bg-brand-600 text-white px-6 py-2.5 rounded-lg hover:bg-brand-700 transition font-medium shadow-sm">
                {{ $homeLabel }}
            </a>
            <a href="{{ $homeUrl }}" onclick="if (history.length > 1) { history.back(); return false; }"
               class="w-full sm:w-auto text-sm font-semibold text-surface-600 hover:text-surface-800 px-6 py-2.5 transition">
                {{ __('app.error.go_back') }}
            </a>
        </div>
    </div>
@endsection
