@extends('layouts.auth')

@section('title', __('app.error.419_title'))

@section('content')
    <div class="text-center">
        <span class="inline-block text-xs font-mono font-semibold tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full mb-5">
            {{ __('app.error.error_code', ['code' => '419']) }}
        </span>

        <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center">
            <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
        </div>

        <h1 class="text-xl font-bold text-surface-900 mb-2">{{ __('app.error.419_heading') }}</h1>
        <p class="text-sm text-surface-500 mb-8">{{ __('app.error.419_message') }}</p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url()->current() }}" class="w-full sm:w-auto bg-brand-600 text-white px-6 py-2.5 rounded-lg hover:bg-brand-700 transition font-medium shadow-sm">
                {{ __('app.error.refresh_page') }}
            </a>
            <a href="/login" class="w-full sm:w-auto text-sm font-semibold text-surface-600 hover:text-surface-800 px-6 py-2.5 transition">
                {{ __('app.error.go_to_login') }}
            </a>
        </div>
    </div>
@endsection
