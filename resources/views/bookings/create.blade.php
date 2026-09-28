@extends('layouts.app')

@section('page-title', __('app.booking.new_booking'))

@section('content')
    <x-ui.page-header :title="__('app.booking.new_booking')">
        <x-slot:actions>
            <a href="/bookings" class="ls-btn ls-btn--secondary">&larr; {{ __('app.btn.back_to_bookings') }}</a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('bookings._form')
@endsection
