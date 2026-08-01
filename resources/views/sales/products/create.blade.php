@extends('layouts.app')

@section('page-title', __('app.sales.add_product'))

@section('content')
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('products.index') }}" class="text-sm text-blue-600 hover:text-blue-800 mb-4 inline-block">&larr; {{ __('app.sales.back_to_products') }}</a>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-6">{{ __('app.sales.add_product') }}</h1>

            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                @include('sales.products._form', ['product' => null])

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                        {{ __('app.sales.add_product') }}
                    </button>
                    <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('app.common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
