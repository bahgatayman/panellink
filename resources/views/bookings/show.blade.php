@extends('layouts.app')

@section('page-title', __('app.booking.bookings') . ' #' . str_pad($booking->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="/bookings" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.btn.back_to_bookings') }}</a>
        </div>
        @if (in_array($booking->status, ['pending', 'confirmed']))
            <a href="/bookings/{{ $booking->id }}/edit" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                {{ __('app.booking.edit_booking') }}
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.booking.bookings') }} #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ __('app.table.th.created') }} {{ $booking->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @php
                        $colors = ['yellow' => 'bg-yellow-100 text-yellow-800', 'blue' => 'bg-blue-100 text-blue-800', 'green' => 'bg-green-100 text-green-800', 'red' => 'bg-red-100 text-red-800'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $colors[$booking->statusColor()] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $booking->statusLabel() }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.room') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">
                            <a href="/workspaces/{{ $booking->room->workspace->id }}" class="text-blue-600 hover:underline">
                                {{ $booking->room->workspace?->name }}
                            </a>
                            / {{ $booking->room->name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.user') }}</dt>
                        <dd class="font-medium mt-1">
                                    <a href="/users/{{ $booking->hotspotUser->id }}" class="text-blue-600 hover:underline">{{ $booking->hotspotUser->name }}</a>
                                    @if ($booking->party_size > 1)
                                        <span class="text-gray-500 text-sm">&middot; {{ __('app.session.party_of', ['count' => $booking->party_size]) }}</span>
                                    @endif
                                    @if ($booking->hotspotUser->phone)
                                        <span class="text-gray-500"> &middot; {{ $booking->hotspotUser->phone }}</span>
                                    @endif
                                    @if ($booking->hotspotUser->email)
                                        <span class="text-gray-500 block">{{ $booking->hotspotUser->email }}</span>
                                    @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.date') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $booking->booking_date->format('l, M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.common.time') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $booking->timeRange() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.booking.duration') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">{{ $booking->total_hours }} {{ __('app.common.hours') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('app.workspace.price_per_hour') }}</dt>
                        <dd class="text-gray-900 font-medium mt-1">ج.م {{ number_format($booking->price_per_hour, 2) }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-gray-500">{{ __('app.booking.total') }}</dt>
                        <dd class="text-2xl font-bold text-blue-600 mt-1">ج.م {{ number_format($booking->total_price, 2) }}</dd>
                    </div>
                </dl>

                @if ($booking->notes)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <dt class="text-sm text-gray-500">{{ __('app.booking.notes') }}</dt>
                        <dd class="text-sm text-gray-900 mt-1">{{ $booking->notes }}</dd>
                    </div>
                @endif
            </div>

            @php $canSell = auth('owner')->user()->hasFeature('sales'); @endphp
            @if ($canSell)
                @php $sale = $booking->sale; @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.sales.items_extras') }}</h3>

                    @if ($sale && $sale->items->isNotEmpty())
                        <div class="overflow-x-auto -mx-1 px-1">
                        <table class="w-full text-sm mb-4">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($sale->items as $item)
                                    <tr>
                                        <td class="py-2 text-gray-900">{{ $item->name }}</td>
                                        <td class="py-2 text-center text-gray-500">&times;{{ $item->quantity }}</td>
                                        <td class="py-2 text-right text-gray-900 font-medium">ج.م {{ number_format($item->line_total, 2) }}</td>
                                        <td class="py-2 text-right w-8">
                                            <form method="POST" action="{{ route('bookings.items.remove', [$booking->id, $item->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-gray-400 hover:text-red-600" title="{{ __('app.common.delete') }}">&times;</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 mb-4">{{ __('app.sales.no_items_yet') }}</p>
                    @endif

                    @if ($products->isNotEmpty())
                        <form method="POST" action="{{ route('bookings.items.add', $booking->id) }}" class="flex items-end gap-2 pt-4 border-t border-gray-100">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.sales.product') }}</label>
                                <select name="product_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} — ج.م {{ number_format($product->price, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-20">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.sales.qty') }}</label>
                                <input type="number" name="quantity" value="1" min="1" max="1000"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                                {{ __('app.sales.add') }}
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-gray-400 pt-4 border-t border-gray-100">
                            {{ __('app.sales.no_products_hint') }}
                            <a href="{{ route('products.create') }}" class="text-blue-600 hover:underline">{{ __('app.sales.add_product') }}</a>
                        </p>
                    @endif

                    <div class="mt-5 pt-4 border-t border-gray-200 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>{{ __('app.sales.room_charge') }}</span>
                            <span>ج.م {{ number_format($booking->total_price, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>{{ __('app.sales.items') }}</span>
                            <span>ج.م {{ number_format($sale->total ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-gray-900 pt-1">
                            <span>{{ __('app.sales.grand_total') }}</span>
                            <span class="text-blue-600">ج.م {{ number_format($booking->grandTotal(), 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.common.actions') }}</h3>

                @if ($booking->status === 'pending')
                    <form method="POST" action="/bookings/{{ $booking->id }}/status" class="space-y-2">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            {{ __('app.btn.confirm_booking') }}
                        </button>
                    </form>
                    <form method="POST" action="/bookings/{{ $booking->id }}/status">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="w-full bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition text-sm font-medium"
                                onclick="return confirm('{{ __('app.booking.cancel_booking') }}')">
                            {{ __('app.btn.cancel_booking') }}
                        </button>
                    </form>
                @elseif ($booking->status === 'confirmed')
                    <form method="POST" action="/bookings/{{ $booking->id }}/status" class="space-y-2">
                        @csrf
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium">
                            {{ __('app.btn.mark_completed') }}
                        </button>
                    </form>
                    <form method="POST" action="/bookings/{{ $booking->id }}/status">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="w-full bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 transition text-sm font-medium"
                                onclick="return confirm('{{ __('app.booking.cancel_booking') }}')">
                            {{ __('app.btn.cancel_booking') }}
                        </button>
                    </form>
                @elseif ($booking->status === 'cancelled')
                    <form method="POST" action="/bookings/{{ $booking->id }}" onsubmit="return confirm('{{ __('app.booking.delete_booking') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                            {{ __('app.btn.delete_booking') }}
                        </button>
                    </form>
                @elseif ($booking->status === 'completed')
                    <p class="text-sm text-green-600 font-medium text-center">{{ __('app.status.completed') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection