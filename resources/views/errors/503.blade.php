@extends('layouts.auth')

@section('title', __('app.error.503_title'))

@section('content')
    <div class="text-center">
        <span class="inline-block text-xs font-mono font-semibold tracking-wider text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full mb-5">
            {{ __('app.error.error_code', ['code' => '503']) }}
        </span>

        <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center">
            <svg class="w-8 h-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            </svg>
        </div>

        <h1 class="text-xl font-bold text-surface-900 mb-2">{{ __('app.error.503_heading') }}</h1>
        <p class="text-sm text-surface-500 mb-8">{{ __('app.error.503_message') }}</p>

        <div class="flex items-center justify-center">
            <a href="{{ url()->current() }}" class="w-full sm:w-auto bg-brand-600 text-white px-6 py-2.5 rounded-lg hover:bg-brand-700 transition font-medium shadow-sm">
                {{ __('app.error.refresh_page') }}
            </a>
        </div>
    </div>
@endsection
