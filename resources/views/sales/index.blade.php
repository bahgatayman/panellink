@extends('layouts.app')

@section('page-title', __('app.sales.sales'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.sales.sales') }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <p class="text-sm text-gray-500">{{ __('app.sales.this_month') }}</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">ج.م {{ number_format($monthTotal, 2) }}</p>
    </div>

    @if($sales->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
            <p class="text-gray-500 text-sm">{{ __('app.sales.no_sales') }}</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-3 font-medium">#</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.sales.date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.sales.customer') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.sales.items') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('app.sales.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sales as $sale)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('sales.show', $sale) }}'">
                                <td class="px-6 py-4 font-medium text-gray-900">#{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ optional($sale->sold_at)->format('M d, Y h:i A') ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $sale->hotspotUser?->name ?? __('app.sales.walk_in') }}
                                    @if($sale->booking_id)
                                        <span class="text-xs text-gray-400 block">{{ __('app.sales.from_booking', ['id' => str_pad($sale->booking_id, 4, '0', STR_PAD_LEFT)]) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $sale->items_count }}</td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900">ج.م {{ number_format($sale->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $sales->links() }}</div>
    @endif
@endsection
