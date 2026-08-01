@extends('layouts.app')

@section('page-title', __('app.session.open_new_session'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('shared-sessions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.btn.back_to_shared_sessions') }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.session.open_new_session') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('shared-sessions.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.session.room') }}</label>
                    <select name="room_id" required
                            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">{{ __('app.placeholder.select_room') }}</option>
                        @foreach($sharedRooms as $room)
                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->workspace->name }} &rarr; {{ $room->name }}
                            ({{ $room->open_sessions_count ?? 0 }}/{{ $room->capacity }} {{ __('app.session.occupied') }})
                        </option>
                        @endforeach
                    </select>
                    @error('room_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5 relative">
                    @include('partials.member-picker', [
                        'label'        => __('app.session.user'),
                        'inputClass'   => 'w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm',
                        'resultsClass' => 'max-w-md',
                    ])
                    @error('hotspot_user_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.session.date') }}</label>
                    <input type="date" name="session_date"
                        value="{{ old('session_date', now()->format('Y-m-d')) }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    @error('session_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.session.start') }}</label>
                    <input type="time" name="start_time"
                        value="{{ old('start_time', now()->format('H:i')) }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    @error('start_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition shadow-sm">
                    {{ __('app.session.open_session') }}
                </button>
            </form>
        </div>

        <div class="bg-blue-50 rounded-xl p-6 sticky top-6 h-fit">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.session.how_shared_sessions_work') }}</h3>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex gap-2">
                    <span class="text-blue-500 font-bold">1.</span>
                    <p>{{ __('app.session.session_open_instructions') }}</p>
                </div>
                <div class="flex gap-2">
                    <span class="text-blue-500 font-bold">2.</span>
                    <p>The session stays open until you manually close it.</p>
                </div>
                <div class="flex gap-2">
                    <span class="text-blue-500 font-bold">3.</span>
                    <p>When you close it, the system calculates the total and saves it as a completed booking.</p>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-blue-100">
                <p class="text-xs text-gray-500">Room capacity = max simultaneous open sessions</p>
            </div>
        </div>
    </div>

@endsection
