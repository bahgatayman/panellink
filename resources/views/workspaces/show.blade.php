@extends('layouts.app')

@section('page-title', $workspace->name)

@php
    $rooms          = $workspace->rooms;
    $availableRooms = $rooms->where('is_available', true)->count();
    $totalCapacity  = (int) $rooms->sum('capacity');
    $minPrice       = $rooms->min('price_per_hour');
    $maxPrice       = $rooms->max('price_per_hour');

    // Tailwind needs the full class name at build time, so map rather than interpolate.
    $tones = [
        'blue'   => ['bar' => 'bg-blue-500',   'chip' => 'bg-blue-50 text-blue-700',     'icon' => 'text-blue-600'],
        'purple' => ['bar' => 'bg-purple-500', 'chip' => 'bg-purple-50 text-purple-700', 'icon' => 'text-purple-600'],
        'green'  => ['bar' => 'bg-green-500',  'chip' => 'bg-green-50 text-green-700',   'icon' => 'text-green-600'],
        'orange' => ['bar' => 'bg-orange-500', 'chip' => 'bg-orange-50 text-orange-700', 'icon' => 'text-orange-600'],
        'gray'   => ['bar' => 'bg-gray-400',   'chip' => 'bg-gray-100 text-gray-600',    'icon' => 'text-gray-500'],
    ];
@endphp

@section('content')
    <a href="{{ route('workspaces.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1">
        <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('app.btn.back_to_workspaces') }}
    </a>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mt-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-4">{{ session('error') }}</div>
    @endif

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-4">
        <div class="h-1.5 {{ $workspace->is_active ? 'bg-green-400' : 'bg-gray-300' }}"></div>

        <div class="p-5 lg:p-6 flex flex-col lg:flex-row lg:items-center gap-4">
            <div class="w-12 h-12 shrink-0 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900 truncate">{{ $workspace->name }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $workspace->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $workspace->is_active ? __('app.status.active') : __('app.status.inactive') }}
                    </span>
                </div>
                @if($workspace->city || $workspace->phone)
                    <p class="text-sm text-gray-500 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                        @if($workspace->city)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $workspace->city }}
                            </span>
                        @endif
                        @if($workspace->phone)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $workspace->phone }}
                            </span>
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('workspaces.edit', $workspace) }}"
                   class="text-sm px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-gray-700">
                    {{ __('app.common.edit') }}
                </a>
                <form method="POST" action="{{ route('workspaces.toggle', $workspace) }}">
                    @csrf
                    <button class="text-sm px-3 py-1.5 border rounded-lg transition
                        {{ $workspace->is_active ? 'border-yellow-200 text-yellow-700 hover:bg-yellow-50' : 'border-green-200 text-green-700 hover:bg-green-50' }}">
                        {{ $workspace->is_active ? __('app.btn.deactivate') : __('app.btn.activate') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('workspaces.destroy', $workspace) }}"
                      onsubmit="return confirm('{{ __('app.workspace.delete_workspace_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm px-3 py-1.5 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition">
                        {{ __('app.common.delete') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- At-a-glance figures --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 border-t border-gray-100 divide-x rtl:divide-x-reverse divide-gray-100">
            <div class="px-5 py-3.5">
                <p class="text-xs text-gray-500">{{ __('app.workspace.total_rooms') }}</p>
                <p class="text-xl font-bold text-gray-900 mt-0.5">{{ $rooms->count() }}</p>
            </div>
            <div class="px-5 py-3.5">
                <p class="text-xs text-gray-500">{{ __('app.workspace.available_rooms') }}</p>
                <p class="text-xl font-bold text-gray-900 mt-0.5">{{ $availableRooms }}</p>
            </div>
            <div class="px-5 py-3.5 border-t lg:border-t-0 border-gray-100">
                <p class="text-xs text-gray-500">{{ __('app.workspace.total_capacity') }}</p>
                <p class="text-xl font-bold text-gray-900 mt-0.5">{{ $totalCapacity }}</p>
            </div>
            <div class="px-5 py-3.5 border-t lg:border-t-0 border-gray-100">
                <p class="text-xs text-gray-500">{{ __('app.workspace.price_range') }}</p>
                <p class="text-xl font-bold text-gray-900 mt-0.5">
                    @if($rooms->isEmpty())
                        —
                    @elseif($minPrice == $maxPrice)
                        {{ number_format($minPrice, 0) }}<span class="text-xs font-normal text-gray-400"> ج.م{{ __('app.common.slash_hr') }}</span>
                    @else
                        {{ number_format($minPrice, 0) }}–{{ number_format($maxPrice, 0) }}<span class="text-xs font-normal text-gray-400"> ج.م{{ __('app.common.slash_hr') }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ── Address / description ───────────────────────────────────────── --}}
    @if($workspace->address || $workspace->description)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:p-6 mt-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ __('app.workspace.details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @if($workspace->address)
                    <div>
                        <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.workspace.address') }}</span>
                        <p class="mt-1 text-sm text-gray-900">{{ $workspace->address }}</p>
                    </div>
                @endif
                @if($workspace->description)
                    <div class="{{ $workspace->address ? '' : 'md:col-span-2' }}">
                        <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.workspace.description') }}</span>
                        <p class="mt-1 text-sm text-gray-900">{{ $workspace->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Rooms ───────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mt-8 mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('app.workspace.rooms') }}</h2>
        <a href="{{ route('rooms.create', $workspace) }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            + {{ __('app.btn.add_room') }}
        </a>
    </div>

    @if($roomsByType->isEmpty())
        <div class="text-center py-14 bg-white rounded-xl border border-gray-100">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500 text-sm mb-4">{{ __('app.empty.no_rooms') }}</p>
            <a href="{{ route('rooms.create', $workspace) }}"
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                + {{ __('app.btn.add_room') }}
            </a>
        </div>
    @else
        @foreach($roomsByType as $type => $roomsOfType)
            @php $tone = $tones[$roomsOfType->first()->typeColor()] ?? $tones['gray']; @endphp
            <div class="mb-8">
                <h3 class="flex items-center gap-2 mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider px-2 py-1 rounded-md {{ $tone['chip'] }}">
                        {{ $roomsOfType->first()->typeLabel() }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $roomsOfType->count() }}</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($roomsOfType as $room)
                        <div class="group bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col transition hover:shadow-md hover:border-blue-200">
                            <div class="h-1 {{ $tone['bar'] }} {{ $room->is_available ? '' : 'opacity-30' }}"></div>

                            {{-- The card body is a real link: rooms have no detail screen,
                                 so it opens the room's edit form. --}}
                            <a href="{{ route('rooms.edit', [$workspace, $room]) }}"
                               title="{{ __('app.workspace.open_room') }}"
                               class="block flex-1 p-4 transition hover:bg-gray-50/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <span class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 transition truncate">{{ $room->name }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full shrink-0
                                        {{ $room->is_available ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                        {{ $room->is_available ? __('app.status.available') : __('app.status.unavailable') }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-4 text-sm">
                                    <span class="inline-flex items-center gap-1.5 text-gray-600">
                                        <svg class="w-4 h-4 {{ $tone['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $room->capacity }} {{ __('app.workspace.seats') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 font-medium text-gray-900">
                                        ج.م {{ number_format($room->price_per_hour, 0) }}
                                        <span class="text-xs font-normal text-gray-400">{{ __('app.common.slash_hr') }}</span>
                                    </span>
                                </div>

                                @if($room->isShared())
                                    {{-- Live seat usage — the number that decides whether a
                                         walk-in can be seated right now. --}}
                                    @php
                                        $free = $room->availableSharedSlots();
                                        $used = max(0, $room->capacity - $free);
                                    @endphp
                                    <div class="mt-3">
                                        <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                            <div class="h-1.5 rounded-full {{ $free === 0 ? 'bg-red-500' : 'bg-green-500' }}"
                                                 style="width: {{ $room->capacity > 0 ? min(100, ($used / $room->capacity) * 100) : 0 }}%"></div>
                                        </div>
                                        <p class="text-xs mt-1.5 {{ $free === 0 ? 'text-red-600' : 'text-gray-500' }}">
                                            {{ $free === 0
                                                ? __('app.workspace.seats_full')
                                                : __('app.workspace.seats_free', ['count' => $free, 'total' => $room->capacity]) }}
                                        </p>
                                    </div>
                                @endif

                                @if($room->description)
                                    <p class="text-xs text-gray-400 mt-3">{{ Str::limit($room->description, 70) }}</p>
                                @endif
                            </a>

                            <div class="flex items-center gap-3 px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                                <form method="POST" action="{{ route('rooms.toggle', [$workspace, $room]) }}">
                                    @csrf
                                    <button class="text-xs font-medium {{ $room->is_available ? 'text-yellow-600 hover:text-yellow-700' : 'text-green-600 hover:text-green-700' }}">
                                        {{ $room->is_available ? __('app.workspace.mark_unavailable') : __('app.workspace.mark_available') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('rooms.destroy', [$workspace, $room]) }}"
                                      class="ms-auto"
                                      onsubmit="return confirm('{{ __('app.workspace.delete_room_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-medium text-red-500 hover:text-red-600">{{ __('app.common.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
@endsection
