@extends('layouts.app')

@section('page-title', __('app.financials.transaction') . ' #' . str_pad($booking->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('financials.transactions') }}" class="text-sm text-blue-600 hover:text-blue-800 mb-4 inline-block">&larr; {{ __('app.financials.back_to_transactions') }}</a>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ __('app.financials.transaction') }} #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $booking->booking_date->format('l, M d, Y') }} &middot; {{ $booking->timeRange() }}</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $booking->statusBadgeClass() }}">
                    {{ $booking->statusLabel() }}
                </span>
            </div>

            @if ($booking->status !== 'completed')
                <div class="bg-gray-50 border border-gray-100 text-gray-500 text-xs rounded-lg px-3 py-2 mb-6">
                    {{ __('app.financials.not_counted') }}
                </div>
            @endif

            <dl class="grid grid-cols-2 gap-4 text-sm mb-6">
                <div>
                    <dt class="text-gray-500">{{ __('app.financials.customer') }}</dt>
                    <dd class="text-gray-900 font-medium mt-1">{{ $booking->hotspotUser?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('app.financials.room') }}</dt>
                    <dd class="text-gray-900 font-medium mt-1">{{ $booking->room?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('app.financials.origin') }}</dt>
                    <dd class="text-gray-900 font-medium mt-1">
                        {{ $booking->sharedSession ? __('app.financials.origin_shared_session', ['id' => $booking->sharedSession->id]) : __('app.financials.origin_direct') }}
                    </dd>
                </div>
                @if ($createdBy)
                    <div>
                        <dt class="text-gray-500">{{ __('app.financials.created_by') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $createdBy['name'] }}</dd>
                    </div>
                @endif
            </dl>

            <div class="border-t border-gray-100 pt-4 mb-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ __('app.financials.room_charge') }} ({{ $booking->total_hours }}h &times; ج.م {{ number_format($booking->price_per_hour, 2) }})</span>
                    <span class="font-medium text-gray-900">ج.م {{ number_format($booking->total_price, 2) }}</span>
                </div>
            </div>

            @if ($booking->sale && $booking->sale->status === 'completed' && $booking->sale->items->isNotEmpty())
                <div class="border-t border-gray-100 pt-4 mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('app.financials.products') }}</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 font-medium">{{ __('app.sales.item') }}</th>
                                <th class="py-2 font-medium text-center">{{ __('app.sales.qty') }}</th>
                                <th class="py-2 font-medium text-right">{{ __('app.sales.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($booking->sale->items as $item)
                                <tr>
                                    <td class="py-2 text-gray-900">{{ $item->name }}</td>
                                    <td class="py-2 text-center text-gray-600">{{ $item->quantity }}</td>
                                    <td class="py-2 text-right text-gray-900">ج.م {{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ((float) $booking->sale->discount_total > 0 || (float) $booking->sale->tax_total > 0)
                        <div class="mt-2 space-y-1 text-xs text-gray-500">
                            @if ((float) $booking->sale->discount_total > 0)
                                <div class="flex justify-between"><span>{{ __('app.sales.total') }} &ndash; discount</span><span>-ج.م {{ number_format($booking->sale->discount_total, 2) }}</span></div>
                            @endif
                            @if ((float) $booking->sale->tax_total > 0)
                                <div class="flex justify-between"><span>tax</span><span>ج.م {{ number_format($booking->sale->tax_total, 2) }}</span></div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                <span class="font-semibold text-gray-700">{{ __('app.financials.grand_total') }}</span>
                <span class="text-xl font-bold text-blue-600">ج.م {{ number_format($booking->grandTotal(), 2) }}</span>
            </div>
        </div>
    </div>
@endsection
