@extends('layouts.app')

@section('page-title', __('app.financials.transactions'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.financials.transactions') }}</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('financials.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                {{ __('app.financials.overview') }}
            </a>
            <a href="{{ route('financials.export', request()->query()) }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 text-white hover:bg-brand-700 transition">
                {{ __('app.financials.export') }}
            </a>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        @include('financials._period-filter')

        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="period" value="{{ $periodKey }}">
            <input type="hidden" name="start" value="{{ $customStart }}">
            <input type="hidden" name="end" value="{{ $customEnd }}">
            <select name="status" onchange="this.form.submit()" class="border border-gray-200 rounded-md text-sm px-2 py-1.5 text-gray-700">
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>{{ __('app.financials.status_completed') }}</option>
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('app.financials.status_all') }}</option>
            </select>
            <select name="source" onchange="this.form.submit()" class="border border-gray-200 rounded-md text-sm px-2 py-1.5 text-gray-700">
                <option value="all" {{ $source === 'all' ? 'selected' : '' }}>{{ __('app.financials.source_all') }}</option>
                <option value="direct_booking" {{ $source === 'direct_booking' ? 'selected' : '' }}>{{ __('app.financials.source_direct_booking') }}</option>
                <option value="shared_session" {{ $source === 'shared_session' ? 'selected' : '' }}>{{ __('app.financials.source_shared_session') }}</option>
                <option value="with_products" {{ $source === 'with_products' ? 'selected' : '' }}>{{ __('app.financials.source_with_products') }}</option>
            </select>
        </form>
    </div>

    @if ($bookings->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
            <p class="text-gray-500 text-sm">{{ __('app.financials.no_transactions') }}</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-3 font-medium">{{ __('app.financials.booking_number') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.financials.date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.financials.customer') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.financials.room') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.financials.origin') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.financials.status') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('app.financials.grand_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($bookings as $booking)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('financials.transactions.show', $booking) }}'">
                                <td class="px-6 py-4 font-medium text-gray-900">#{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $booking->booking_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $booking->hotspotUser?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $booking->room?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    {{ $booking->sharedSession ? __('app.financials.origin_shared_session', ['id' => $booking->sharedSession->id]) : __('app.financials.origin_direct') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $booking->statusBadgeClass() }}">
                                        {{ $booking->statusLabel() }}
                                    </span>
                                    @if ($booking->status !== 'completed')
                                        <span class="block text-[11px] text-gray-400 mt-0.5">{{ __('app.financials.not_counted') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900">ج.م {{ number_format($booking->grandTotal(), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $bookings->links() }}</div>
    @endif
@endsection
