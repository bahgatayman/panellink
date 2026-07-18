@extends('layouts.app')

@section('page-title', __('app.workspace.my_workspaces'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.workspace.my_workspaces') }}</h1>
        <a href="{{ route('workspaces.create') }}"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            + {{ __('app.btn.add_workspace') }}
        </a>
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

    @if($workspaces->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500 text-sm mb-4">{{ __('app.empty.no_workspaces') }}</p>
            <a href="{{ route('workspaces.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                + {{ __('app.btn.create_workspace') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($workspaces as $workspace)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="h-2 {{ $workspace->is_active ? 'bg-green-400' : 'bg-gray-300' }}"></div>
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-semibold text-gray-900">{{ $workspace->name }}</h3>
                            <span class="text-xs px-2 py-1 rounded-full
                                {{ $workspace->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $workspace->is_active ? __('app.status.active') : __('app.status.inactive') }}
                            </span>
                        </div>
                        @if($workspace->city)
                            <p class="text-sm text-gray-500 flex items-center gap-1 mb-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $workspace->city }}
                            </p>
                        @endif
                        <p class="text-sm text-gray-400">{{ $workspace->rooms_count }} {{ __('app.workspace.rooms') }}</p>
                    </div>
                    <div class="px-6 pb-4 flex gap-3">
                        <a href="{{ route('workspaces.show', $workspace) }}"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">{{ __('app.common.view') }}</a>
                        <a href="{{ route('workspaces.edit', $workspace) }}"
                            class="text-sm text-gray-600 hover:text-gray-800">{{ __('app.common.edit') }}</a>
                        <form method="POST" action="{{ route('workspaces.toggle', $workspace) }}">
                            @csrf
                            <button class="text-sm {{ $workspace->is_active ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ $workspace->is_active ? __('app.btn.deactivate') : __('app.btn.activate') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $workspaces->links() }}
        </div>
    @endif
@endsection
