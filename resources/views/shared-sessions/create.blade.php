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
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.session.room') }}</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($sharedRooms as $room)
                            @php
                                $available = max(0, $room->capacity - ($room->occupied_seats ?? 0));
                                $isFull = $available <= 0;
                                $used = $room->capacity - $available;
                            @endphp
                            <label class="room-card relative flex flex-col gap-3 rounded-xl border-2 border-gray-200 bg-white p-4 cursor-pointer transition hover:border-blue-300 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:ring-2 has-[:checked]:ring-blue-500/20">
                                <input type="radio" name="room_id" value="{{ $room->id }}"
                                       data-capacity="{{ $room->capacity }}" data-available="{{ $available }}"
                                       {{ old('room_id') == $room->id ? 'checked' : '' }}
                                       required class="peer sr-only room-radio">

                                <span class="pointer-events-none absolute top-3 end-3 hidden h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white peer-checked:flex">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>

                                <div class="pe-6">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $room->workspace->name }} &rarr; {{ $room->name }}</p>
                                    <span class="inline-flex mt-1.5 text-xs px-2 py-0.5 rounded-full {{ $isFull ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                                        {{ $isFull ? __('app.workspace.seats_full') : __('app.workspace.seats_free', ['count' => $available, 'total' => $room->capacity]) }}
                                    </span>
                                </div>

                                <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $isFull ? 'bg-red-500' : 'bg-green-500' }}"
                                         style="width: {{ $room->capacity > 0 ? min(100, ($used / $room->capacity) * 100) : 0 }}%"></div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('room_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.session.party_size') }}</label>
                    <input type="number" name="party_size" id="party-size-input" min="1"
                        value="{{ old('party_size', 1) }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <p class="text-xs text-gray-400 mt-1">{{ __('app.session.party_size_hint') }}</p>
                    @error('party_size') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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
                <p class="text-xs text-gray-500">{{ __('app.session.capacity_hint') }}</p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const roomRadios = document.querySelectorAll('.room-radio');
        const partyInput = document.getElementById('party-size-input');

        function syncMax() {
            const checked = document.querySelector('.room-radio:checked');
            const available = checked ? parseInt(checked.dataset.available || '0', 10) : null;
            if (available) {
                partyInput.max = available;
                if (parseInt(partyInput.value, 10) > available) partyInput.value = available;
            } else {
                partyInput.removeAttribute('max');
            }
        }

        roomRadios.forEach(radio => radio.addEventListener('change', syncMax));
        syncMax();
    })();
    </script>
@endsection
