@extends('layouts.app')

@section('page-title', __('app.booking.edit_booking') . ' #' . str_pad($booking->id, 4, '0', STR_PAD_LEFT))

@section('content')
    <x-ui.page-header :title="__('app.booking.edit_booking').' #'.str_pad($booking->id, 4, '0', STR_PAD_LEFT)">
        <x-slot:actions>
            <a href="/bookings/{{ $booking->id }}" class="ls-btn ls-btn--secondary">&larr; {{ __('app.btn.back_to_bookings') }}</a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('bookings._form')
@endsection
