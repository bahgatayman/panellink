@extends('layouts.app')

@section('page-title', __('app.coupons.edit'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.coupons.edit') }}</h1>
        <a href="{{ route('coupons.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.common.back') }}</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('coupons.update', $coupon->id) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('coupons._form')
    </form>
@endsection
