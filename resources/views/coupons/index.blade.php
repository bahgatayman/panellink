@extends('layouts.app')

@section('page-title', __('app.coupons.title'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.coupons.title') }}</h1>
        @if ($canCreate)
            <button type="button" data-ls-open="create-coupon" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                <x-ui.icon name="plus" class="w-4 h-4" />
                {{ __('app.coupons.add') }}
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('coupons.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs text-gray-500 mb-1">{{ __('app.common.search') }}</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('app.coupons.search_placeholder') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" dir="ltr">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('app.common.status') }}</label>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">{{ __('app.coupons.filter_all') }}</option>
                @foreach (['active', 'inactive', 'scheduled', 'expired', 'limit_reached'] as $key)
                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ __('app.coupons.status_'.$key) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm font-medium">
            {{ __('app.common.search') }}
        </button>
    </form>

    @if ($coupons->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
            <p class="text-gray-500 text-sm">{{ $search || $status ? __('app.coupons.no_match') : __('app.coupons.empty') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($coupons as $coupon)
                @php
                    // Full literal class strings (never interpolated fragments) so
                    // Tailwind's scanner can always find them regardless of which
                    // build pipeline is active for this page.
                    $accent = match ($coupon->statusTone()) {
                        'ok' => ['stripe' => 'bg-green-400', 'chip' => 'bg-green-50', 'icon' => 'text-green-600', 'value' => 'text-green-600'],
                        'info' => ['stripe' => 'bg-blue-400', 'chip' => 'bg-blue-50', 'icon' => 'text-blue-600', 'value' => 'text-blue-600'],
                        'warn' => ['stripe' => 'bg-amber-400', 'chip' => 'bg-amber-50', 'icon' => 'text-amber-600', 'value' => 'text-amber-600'],
                        'danger' => ['stripe' => 'bg-red-400', 'chip' => 'bg-red-50', 'icon' => 'text-red-600', 'value' => 'text-red-600'],
                        default => ['stripe' => 'bg-gray-300', 'chip' => 'bg-gray-100', 'icon' => 'text-gray-500', 'value' => 'text-gray-700'],
                    };
                    $isMuted = in_array($coupon->statusKey(), ['inactive', 'expired', 'limit_reached'], true);
                    $appliesToIcon = match ($coupon->applies_to) {
                        'rooms' => 'building',
                        'products' => 'box',
                        default => 'tag',
                    };
                    $usagePercent = $coupon->usage_limit
                        ? min(100, (int) round($coupon->usedCount() / max(1, $coupon->usage_limit) * 100))
                        : null;
                    $barColor = $usagePercent >= 100 ? 'bg-red-500' : ($usagePercent >= 75 ? 'bg-amber-500' : 'bg-blue-500');
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 border-s-4 {{ $accent['stripe'] }} overflow-hidden flex flex-col {{ $isMuted ? 'opacity-70' : '' }}">
                    <div class="p-5 flex flex-col gap-4 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-lg {{ $accent['chip'] }} flex items-center justify-center shrink-0">
                                    <x-ui.icon name="tag" class="w-4 h-4 {{ $accent['icon'] }}" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-mono font-semibold text-gray-900 truncate" dir="ltr" title="{{ $coupon->code }}">{{ $coupon->code }}</p>
                                    <p class="text-lg font-bold {{ $accent['value'] }} leading-tight">{{ $coupon->discountLabel() }}</p>
                                </div>
                            </div>
                            <x-ui.badge :tone="$coupon->statusTone()">{{ $coupon->statusLabel() }}</x-ui.badge>
                        </div>

                        <dl class="text-sm text-gray-600 space-y-2.5 border-t border-gray-100 pt-3">
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="{{ $appliesToIcon }}" class="w-4 h-4 text-gray-400 shrink-0" />
                                <dt class="sr-only">{{ __('app.coupons.col_applies_to') }}</dt>
                                <dd class="truncate">
                                    @if ($coupon->applies_to === 'both')
                                        {{ __('app.coupons.both') }}
                                    @elseif ($coupon->applies_to === 'rooms')
                                        {{ $coupon->targetsAllRooms() ? __('app.coupons.all_rooms') : __('app.coupons.specific_rooms') }}
                                    @else
                                        {{ $coupon->targetsAllProducts() ? __('app.coupons.all_products') : __('app.coupons.specific_products') }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-ui.icon name="users" class="w-4 h-4 text-gray-400 shrink-0" />
                                    <dt class="sr-only">{{ __('app.coupons.col_usage') }}</dt>
                                    <dd>
                                        @if ($coupon->usage_limit !== null)
                                            {{ __('app.coupons.used_of', ['used' => $coupon->usedCount(), 'limit' => $coupon->usage_limit]) }}
                                        @else
                                            {{ __('app.coupons.used_count', ['count' => $coupon->usedCount()]) }}
                                        @endif
                                    </dd>
                                </div>
                                @if ($usagePercent !== null)
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-1.5 ms-6">
                                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $usagePercent }}%"></div>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="calendar" class="w-4 h-4 text-gray-400 shrink-0" />
                                <dt class="sr-only">{{ __('app.coupons.col_valid_until') }}</dt>
                                <dd class="whitespace-nowrap">{{ $coupon->expires_at?->format('M d, Y') ?? __('app.coupons.no_expiry') }}</dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 mt-auto">
                            @if ($canEdit)
                                <a href="{{ route('coupons.edit', $coupon->id) }}" class="px-3 py-1.5 rounded-md text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition">
                                    {{ __('app.common.edit') }}
                                </a>
                                <form method="POST" action="{{ route('coupons.toggle', $coupon->id) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 transition">
                                        {{ $coupon->is_active ? __('app.coupons.deactivate') : __('app.coupons.activate') }}
                                    </button>
                                </form>
                            @endif
                            @if ($canDelete)
                                <form method="POST" action="{{ route('coupons.destroy', $coupon->id) }}" onsubmit="return confirm('{{ __('app.coupons.delete_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition">
                                        {{ __('app.common.delete') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $coupons->links() }}</div>
    @endif

    @if ($canCreate)
        @include('coupons._quick-create-modal')
    @endif
@endsection
