@extends('layouts.app')

@section('page-title', __('app.user.add_new_user'))

@section('content')
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            @foreach ($errors->all() as $error)
                <p class="text-sm">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @php
        $owner = auth('owner')->user();
        $plan  = $owner->plan;
    @endphp

    @if($plan)
    <div class="bg-white rounded-xl border p-4 mb-6 max-w-lg mx-auto">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">
                {{ __('app.common.members') }}: {{ $owner->hotspotUsers()->count() }} / {{ $plan->max_members }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                {{ $plan->name }} {{ __('app.common.plan') }}
            </span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2">
            @php $pct = $owner->usagePercentage(); @endphp
            <div class="h-2 rounded-full
                {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-400' : 'bg-green-400') }}"
                style="width: {{ min(100, $pct) }}%">
            </div>
        </div>
        @if($owner->remainingUserSlots() <= 5 && $owner->remainingUserSlots() > 0)
        <p class="text-xs text-yellow-600 mt-2">
            ⚠ {{ __('app.msg.slots_remaining', ['count' => $owner->remainingUserSlots()]) }}
        </p>
        @elseif($owner->remainingUserSlots() == 0)
        <p class="text-xs text-red-600 mt-2">
            ✗ {{ __('app.msg.plan_limit_reached_contact') }}
        </p>
        @endif
    </div>
    @endif

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ __('app.user.add_new_user') }}</h2>

        <form method="POST" action="/users">
            @csrf

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.user.name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('name')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.user.phone') }}</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required inputmode="numeric"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-400 mt-2">This will be the MikroTik login username. Password will be set to the phone number automatically.</p>
                @error('phone')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.user.email') }} (optional)</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('email')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.user.notes') }} (optional)</label>
                <textarea name="notes" id="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-sm">
                {{ __('app.btn.add_user') }} &amp; {{ __('app.user.update_speed_on_mikrotik') }}
            </button>
        </form>
    </div>
@endsection
