@extends('layouts.app')

@section('page-title', __('app.sales.products'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.sales.products') }}</h1>
        <a href="{{ route('products.create') }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            + {{ __('app.sales.add_product') }}
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">{{ session('error') }}</div>
    @endif

    @if($products->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-gray-500 text-sm mb-4">{{ __('app.sales.no_products') }}</p>
            <a href="{{ route('products.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                + {{ __('app.sales.add_product') }}
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-3 font-medium">{{ __('app.sales.name') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.sales.type') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.sales.price') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.common.status') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($products as $product)
                            @php $tc = $product->typeColor(); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                    @if($product->sku)
                                        <div class="text-xs text-gray-400">{{ $product->sku }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs px-2 py-1 rounded-full bg-{{ $tc }}-100 text-{{ $tc }}-700">
                                        {{ $product->isService() ? __('app.sales.service') : __('app.sales.product') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-medium">ج.م {{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $product->is_active ? __('app.status.active') : __('app.status.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('products.edit', $product) }}" class="text-gray-600 hover:text-gray-800">{{ __('app.common.edit') }}</a>
                                        <form method="POST" action="{{ route('products.toggle', $product) }}">
                                            @csrf
                                            <button class="{{ $product->is_active ? 'text-yellow-600' : 'text-green-600' }}">
                                                {{ $product->is_active ? __('app.btn.deactivate') : __('app.btn.activate') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                                            onsubmit="return confirm('{{ __('app.sales.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:text-red-800">{{ __('app.common.delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
@endsection
