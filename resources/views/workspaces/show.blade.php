@extends('layouts.app')

@section('page-title', $workspace->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('workspaces.index') }}" class="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block">&larr; {{ __('app.btn.back_to_workspaces') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $workspace->name }}</h1>
        </div>
        <div class="flex items-center gap-2">
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

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif

    <!-- Workspace Info Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.common.status') }}</span>
                <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $workspace->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $workspace->is_active ? __('app.status.active') : __('app.status.inactive') }}
                    </span>
                </p>
            </div>
            @if($workspace->city)
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.workspace.city') }}</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $workspace->city }}</p>
                </div>
            @endif
            @if($workspace->address)
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.workspace.address') }}</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $workspace->address }}</p>
                </div>
            @endif
            @if($workspace->phone)
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.workspace.phone') }}</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $workspace->phone }}</p>
                </div>
            @endif
            @if($workspace->description)
                <div class="md:col-span-2">
                    <span class="text-xs text-gray-500 uppercase tracking-wider">{{ __('app.workspace.description') }}</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $workspace->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Rooms Header -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('app.workspace.rooms') }}</h2>
        <a href="{{ route('rooms.create', $workspace) }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            + {{ __('app.btn.add_room') }}
        </a>
    </div>

    @if($roomsByType->isEmpty())
        <div class="text-center py-12 bg-white rounded-xl border border-gray-100">
            <p class="text-gray-500 text-sm mb-4">{{ __('app.empty.no_rooms') }}</p>
            <a href="{{ route('rooms.create', $workspace) }}"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                + {{ __('app.btn.add_room') }}
            </a>
        </div>
    @else
        @foreach($roomsByType as $type => $rooms)
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    {{ $rooms->first()->typeLabel() }} ({{ $rooms->count() }})
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($rooms as $room)
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">{{ $room->name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    {{ $room->is_available ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                    {{ $room->is_available ? __('app.status.available') : __('app.status.unavailable') }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 space-y-1">
                                <p>
                                    <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ __('app.workspace.capacity') }}: {{ $room->capacity }}
                                </p>
                                <p>
                                    <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ number_format($room->price_per_hour, 2) }} {{ __('app.common.per_hour') }}
                                </p>
                                @if($room->description)
                                    <p class="text-gray-400 mt-2">{{ Str::limit($room->description, 60) }}</p>
                                @endif
                            </div>
                            <div class="flex gap-3 mt-3 pt-3 border-t border-gray-100">
                                <a href="{{ route('rooms.edit', [$workspace, $room]) }}"
                                    class="text-xs text-blue-600">{{ __('app.common.edit') }}</a>
                                <form method="POST" action="{{ route('rooms.toggle', [$workspace, $room]) }}">
                                    @csrf
                                    <button class="text-xs {{ $room->is_available ? 'text-yellow-600' : 'text-green-600' }}">
                                        {{ $room->is_available ? __('app.workspace.mark_unavailable') : __('app.workspace.mark_available') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('rooms.destroy', [$workspace, $room]) }}"
                                    onsubmit="return confirm('{{ __('app.workspace.delete_room_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-500">{{ __('app.common.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
@endsection
