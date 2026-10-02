@extends('layouts.app')

@section('page-title', __('app.packages.create_title'))

@section('content')
    <x-ui.page-header :title="__('app.packages.create_title')" :eyebrow="__('app.packages.title')" />

    <form method="POST" action="{{ route('packages.store') }}" class="ls-card ls-pkg-card-form">
        @csrf
        <div class="ls-card-body">
            @include('packages._form')
        </div>
        <div class="ls-pkg-form-foot">
            <x-ui.button :href="route('packages.index')" variant="ghost">{{ __('app.packages.cancel') }}</x-ui.button>
            <x-ui.button type="submit" variant="primary">{{ __('app.packages.save') }}</x-ui.button>
        </div>
    </form>
@endsection
