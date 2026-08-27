@extends('layouts.app')

@section('page-title', __('app.financials.title'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.financials.title') }}</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('financials.transactions') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                {{ __('app.financials.view_transactions') }}
            </a>
            <a href="{{ route('financials.export', request()->query()) }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 text-white hover:bg-brand-700 transition">
                {{ __('app.financials.export') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.revenue_today') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-green-600 mt-1">ج.م {{ number_format($revenueToday, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.revenue_this_week') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-green-600 mt-1">ج.م {{ number_format($revenueThisWeek, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.revenue_this_month') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-green-600 mt-1">ج.م {{ number_format($revenueThisMonth, 2) }}</p>
        </div>
    </div>

    <div class="mb-6">
        @include('financials._period-filter')
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.total_revenue') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">ج.م {{ number_format($comparison['current'], 2) }}</p>
            @if ($comparison['changePercent'] !== null)
                @php $up = $comparison['change'] >= 0; @endphp
                <p class="text-xs mt-1 font-medium {{ $up ? 'text-green-600' : 'text-red-600' }}">
                    {{ $up ? '▲' : '▼' }} {{ number_format(abs($comparison['changePercent']), 1) }}% {{ __('app.financials.vs_previous_period') }}
                </p>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.booking_revenue') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-blue-600 mt-1">ج.م {{ number_format($bookingRevenue, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.product_revenue') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-purple-600 mt-1">ج.م {{ number_format($saleRevenue, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.financials.average_booking_value') }}</p>
            <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">ج.م {{ number_format($averageBookingValue ?? 0, 2) }}</p>
        </div>
    </div>

    @if (array_sum($trend) > 0)
        @php $maxTrend = max(1, ...array_values($trend)); @endphp
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:p-6 mb-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.financials.revenue_trend') }}</h3>
            <div class="flex items-end gap-1 lg:gap-2 h-40 overflow-x-auto">
                @foreach ($trend as $date => $amount)
                    <div class="flex-1 min-w-[1.5rem] flex flex-col items-center gap-1 h-full justify-end">
                        <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ $amount > 0 ? number_format($amount, 0) : '' }}</span>
                        <div class="w-full rounded-t-md bg-brand-500" style="height: {{ ($amount / $maxTrend) * 120 }}px; min-height: 2px;"></div>
                        <span class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($date)->format('j M') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6 text-center text-sm text-gray-400">
            {{ __('app.financials.no_data') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.financials.revenue_by_room') }}</h3>
            @if (empty($byRoom))
                <p class="text-sm text-gray-400">{{ __('app.financials.no_data') }}</p>
            @else
                <div class="space-y-2">
                    @foreach ($byRoom as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 truncate">{{ $row['room_name'] }}</span>
                            <span class="font-medium text-gray-900 shrink-0 ms-2">ج.م {{ number_format($row['revenue'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.financials.revenue_by_room_type') }}</h3>
            @if (empty($byRoomType))
                <p class="text-sm text-gray-400">{{ __('app.financials.no_data') }}</p>
            @else
                <div class="space-y-2">
                    @foreach ($byRoomType as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 truncate">{{ Illuminate\Support\Facades\Lang::has('app.room_type.'.$row['type']) ? __('app.room_type.'.$row['type']) : ucfirst($row['type']) }}</span>
                            <span class="font-medium text-gray-900 shrink-0 ms-2">ج.م {{ number_format($row['revenue'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.financials.revenue_by_product') }}</h3>
            @if (empty($byProduct))
                <p class="text-sm text-gray-400">{{ __('app.financials.no_data') }}</p>
            @else
                <div class="space-y-2">
                    @foreach ($byProduct as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 truncate">{{ $row['name'] }}</span>
                            <span class="font-medium text-gray-900 shrink-0 ms-2">ج.م {{ number_format($row['revenue'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
