@extends('layouts.app')

@section('page-title', __('app.profile.my_profile'))

@section('content')
    <div class="max-w-3xl mx-auto">
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Owner Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('app.profile.account_information') }}</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('app.common.name') }}</dt>
                            <dd class="text-gray-900 font-medium">{{ $owner->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('app.common.email') }}</dt>
                            <dd class="text-gray-900">{{ $owner->email }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('app.label.business') }}</dt>
                            <dd class="text-gray-900 font-medium">{{ $owner->business_name }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- MikroTik Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('app.profile.mikrotik_connection') }}</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('app.label.mikrotik_host') }}</dt>
                            <dd class="text-gray-900">{{ $owner->mikrotik_host }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('app.label.mikrotik_port') }}</dt>
                            <dd class="text-gray-900">{{ $owner->mikrotik_port }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ __('app.label.mikrotik_username') }}</dt>
                            <dd class="text-gray-900">{{ $owner->mikrotik_username }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Plan & Subscription -->
            <div class="space-y-6">
                <!-- Current Plan -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl text-white p-6">
                    <p class="text-blue-100 text-sm">{{ __('app.profile.current_plan') }}</p>
                    <p class="text-3xl font-bold mt-1">{{ $owner->plan->name ?? __('app.profile.no_plan') }}</p>
                    <p class="text-blue-100 mt-1">{{ $owner->plan?->formattedPrice() }}</p>
                    <div class="mt-4 pt-4 border-t border-blue-400">
                        <div class="flex justify-between text-sm">
                            <span class="text-blue-100">{{ __('app.profile.members_used') }}</span>
                            <span class="font-semibold">{{ $usageCount }} / {{ $owner->plan?->max_members ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-blue-400 rounded-full h-2 mt-2">
                            @php
                                $pct = $owner->plan
                                    ? ($usageCount / $owner->plan->max_members) * 100
                                    : 0;
                            @endphp
                            <div class="h-2 rounded-full bg-white" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Status -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold text-gray-800 mb-3">{{ __('app.profile.subscription') }}</h2>
                    @php $status = $owner->subscriptionStatus(); @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $status === 'expiring_soon' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $status === 'expired' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $status === 'never' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $status === 'disabled' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                    @if ($owner->subscription_expires_at)
                        <dl class="space-y-2 text-sm mt-3">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('app.profile.expires') }}</dt>
                                <dd class="text-gray-900 font-medium">{{ $owner->subscription_expires_at->format('Y-m-d') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">{{ __('app.profile.days_remaining') }}</dt>
                                <dd class="text-gray-900">{{ $owner->daysUntilExpiry() }}</dd>
                            </div>
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
