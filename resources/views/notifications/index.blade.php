@extends('layouts.app')

@section('page-title', __('app.notif.title'))

@section('content')
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('app.notif.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ trans_choice('app.notif.unread_count', $unreadCount, ['count' => $unreadCount]) }}
            </p>
        </div>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    {{ __('app.notif.mark_all_read') }}
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
        @forelse($notifications as $n)
            @php $c = $n->levelColor(); @endphp
            <div class="flex items-start gap-4 px-4 sm:px-6 py-4 {{ $n->isRead() ? '' : 'bg-blue-50/40' }}">
                <span class="mt-0.5 shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-{{ $c }}-100 text-{{ $c }}-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $n->iconPath() }}"/>
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $n->title }}</h3>
                        @unless($n->isRead())
                            <span class="shrink-0 w-2 h-2 rounded-full bg-blue-500"></span>
                        @endunless
                    </div>
                    @if($n->body)
                        <p class="text-sm text-gray-600 mt-0.5">{{ $n->body }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                        @if($n->action_url)
                            <a href="{{ route('notifications.open', $n->id) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                {{ __('app.common.view') }} →
                            </a>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    @unless($n->isRead())
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                            @csrf
                            <button type="submit" title="{{ __('app.notif.mark_read') }}"
                                    class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('notifications.destroy', $n->id) }}"
                          onsubmit="return confirm('{{ __('app.notif.delete_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="{{ __('app.common.delete') }}"
                                class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="px-6 py-16 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <p class="text-gray-400 text-sm">{{ __('app.notif.empty') }}</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
