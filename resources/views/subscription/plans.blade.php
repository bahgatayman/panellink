@extends('layouts.app')

@section('page-title', __('app.subscription.plans_title'))

@php
    $status    = $owner->subscriptionStatus();
    $daysLeft  = $owner->daysUntilExpiry();
    $usedSlots = $owner->hotspotUsers()->count();
    $maxSlots  = $owner->plan?->max_members ?? 0;
    $usage     = $maxSlots > 0 ? min(100, ($usedSlots / $maxSlots) * 100) : 0;

    $statusTone = match ($status) {
        'active'        => 'bg-green-100 text-green-700',
        'expiring_soon' => 'bg-yellow-100 text-yellow-800',
        default         => 'bg-red-100 text-red-700',
    };
@endphp

@section('content')
    <div class="max-w-5xl mx-auto">

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- ── Where the owner stands today ────────────────────────────── --}}
        <div class="bg-gradient-to-br from-blue-800 to-blue-600 rounded-2xl text-white p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                <div class="min-w-0 flex-1">
                    <p class="text-xs uppercase tracking-wide text-blue-100/80">{{ __('app.profile.current_plan') }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <p class="text-2xl font-bold">{{ $owner->plan->name ?? __('app.profile.no_plan') }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusTone }}">
                            {{ __('app.status.' . ($status === 'expiring_soon' ? 'expiring_soon' : ($status === 'active' ? 'active' : 'expired'))) }}
                        </span>
                    </div>

                    @if ($owner->subscription_expires_at)
                        <p class="text-sm text-blue-100/90 mt-2">
                            @if ($status === 'expired' || $status === 'never')
                                {{ __('app.subscription.expired_since', ['date' => $owner->subscription_expires_at->format('d M Y')]) }}
                            @else
                                {{ __('app.subscription.current_expires') }}
                                <span class="font-semibold">{{ $owner->subscription_expires_at->format('d M Y') }}</span>
                                · {{ __('app.subscription.days_left', ['count' => $daysLeft]) }}
                            @endif
                        </p>
                    @endif
                </div>

                @if ($owner->plan)
                    <div class="sm:w-56 shrink-0">
                        <div class="flex justify-between text-xs text-blue-100/90">
                            <span>{{ __('app.profile.members_used') }}</span>
                            <span class="font-semibold">{{ $usedSlots }} / {{ $maxSlots }}</span>
                        </div>
                        <div class="w-full bg-white/25 rounded-full h-2 mt-2">
                            <div class="h-2 rounded-full {{ $usage >= 100 ? 'bg-red-300' : 'bg-white' }}" style="width: {{ $usage }}%"></div>
                        </div>
                        @if ($usage >= 100)
                            <p class="text-xs text-red-100 mt-2">{{ __('app.subscription.members_in_use', ['used' => $usedSlots, 'total' => $maxSlots]) }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Picker ──────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h1 class="text-lg font-semibold text-gray-900">{{ __('app.subscription.choose_plan') }}</h1>
            <p class="text-sm text-gray-500 mt-1 mb-6">
                {{ __('app.subscription.choose_plan_hint') }}
                @if ($owner->subscription_expires_at && $status !== 'expired')
                    {{ __('app.subscription.stacks_hint') }}
                @endif
            </p>

            @include('partials.plan-picker')
        </div>
    </div>
@endsection
