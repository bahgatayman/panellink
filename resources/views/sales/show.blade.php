@extends('layouts.app')

@section('page-title', __('app.sales.sale') . ' #' . str_pad($sale->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('sales.index') }}" class="text-sm text-blue-600 hover:text-blue-800 mb-4 inline-block">&larr; {{ __('app.sales.back_to_sales') }}</a>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ __('app.sales.sale') }} #{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ optional($sale->sold_at)->format('l, M d, Y h:i A') ?? '—' }}</p>
                </div>
                @php $colors = ['yellow' => 'bg-yellow-100 text-yellow-800', 'green' => 'bg-green-100 text-green-800', 'red' => 'bg-red-100 text-red-800']; @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $colors[$sale->statusColor()] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $sale->statusLabel() }}
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-4 text-sm mb-6">
                <div>
                    <dt class="text-gray-500">{{ __('app.sales.customer') }}</dt>
                    <dd class="text-gray-900 font-medium mt-1">{{ $sale->hotspotUser?->name ?? __('app.sales.walk_in') }}</dd>
                </div>
                @if($sale->booking)
                    <div>
                        <dt class="text-gray-500">{{ __('app.sales.booking') }}</dt>
                        <dd class="mt-1"><a href="/bookings/{{ $sale->booking->id }}" class="text-blue-600 hover:underline">#{{ str_pad($sale->booking->id, 4, '0', STR_PAD_LEFT) }}</a></dd>
                    </div>
                @endif
            </dl>

            <div class="overflow-x-auto">
            <table class="w-full min-w-[26rem] text-sm border-t border-gray-100">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2 font-medium">{{ __('app.sales.item') }}</th>
                        <th class="py-2 font-medium text-center">{{ __('app.sales.qty') }}</th>
                        <th class="py-2 font-medium text-right">{{ __('app.sales.unit_price') }}</th>
                        <th class="py-2 font-medium text-right">{{ __('app.sales.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-3 text-gray-900">{{ $item->name }}</td>
                            <td class="py-3 text-center text-gray-600">{{ $item->quantity }}</td>
                            <td class="py-3 text-right text-gray-600">ج.م {{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-3 text-right text-gray-900 font-medium">ج.م {{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200">
                        <td colspan="3" class="py-3 text-right font-semibold text-gray-700">{{ __('app.sales.total') }}</td>
                        <td class="py-3 text-right text-xl font-bold text-blue-600">ج.م {{ number_format($sale->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>
@endsection
