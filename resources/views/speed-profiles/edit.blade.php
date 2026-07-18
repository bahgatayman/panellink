@extends('layouts.app')

@section('page-title', __('app.speed.edit_profile'))

@section('content')
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            @foreach ($errors->all() as $error)
                <p class="text-sm">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ __('app.speed.edit_profile') }}</h2>

        <form method="POST" action="/speed-profiles/{{ $profile->id }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.speed.name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name', $profile->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('name')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="speed_download" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.speed_download') }}</label>
                    <select name="speed_download" id="speed_download" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @foreach ($speedOptions as $speed)
                            <option value="{{ $speed }}" {{ old('speed_download', $profile->speed_download) === $speed ? 'selected' : '' }}>{{ $speed }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="speed_upload" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.speed_upload') }}</label>
                    <select name="speed_upload" id="speed_upload" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @foreach ($speedOptions as $speed)
                            <option value="{{ $speed }}" {{ old('speed_upload', $profile->speed_upload) === $speed ? 'selected' : '' }}>{{ $speed }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $profile->is_default) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">{{ __('app.speed.set_as_default') }}</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-sm">
                {{ __('app.speed.update_profile') }}
            </button>
        </form>
    </div>
@endsection
