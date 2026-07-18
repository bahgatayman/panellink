<!DOCTYPE html>
@php $locale = app()->getLocale(); $isRtl = $locale === 'ar'; @endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Link Space Panel - {{ $owner->business_name ?? __('app.auth.linkspace') }}</title>
    <link rel="icon" type="image/png" href="/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    @if($isRtl)
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
    @endif
</head>
<body class="bg-[#f8fafc] font-sans antialiased">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Mobile header -->
        <div class="lg:hidden flex items-center justify-between bg-[#0f172a] px-4 py-3">
            <div class="bg-white rounded-md px-2 py-1 inline-flex items-center">
                <img src="/logo.png" alt="Link Space Panel" class="h-8 w-auto">
            </div>
            <div class="flex items-center gap-2">
                <button id="menu-toggle" class="text-white p-2 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Sidebar overlay (mobile) -->
        <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black/50 z-10 hidden" onclick="closeSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-20 w-[260px] bg-gradient-to-b from-[#0f172a] to-[#1e293b] text-white flex flex-col shrink-0 transition-transform duration-300 {{ $isRtl ? 'translate-x-full' : '-translate-x-full' }} lg:translate-x-0">
            <div class="flex items-center justify-center px-6 py-6 border-b border-white/10">
                <img src="/logo.png" alt="Link Space Panel" class="h-16 w-auto rounded-xl bg-white p-2">
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                @php $currentOwner = auth('owner')->user(); @endphp
                <a href="/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('dashboard') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>{{ __('app.nav.dashboard') }}</span>
                </a>
                @if($currentOwner->hasFeature('hotspot'))
                    <a href="/users" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('users*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ __('app.nav.users') }}</span>
                    </a>
                    <a href="/sessions" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('sessions*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                        <span>{{ __('app.nav.active_sessions') }}</span>
                    </a>
                    <a href="/speed-profiles" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('speed-profiles*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>{{ __('app.nav.speed_profiles') }}</span>
                    </a>
                @endif
                @if($currentOwner->hasFeature('workspace'))
                    <a href="/workspaces" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('workspaces*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>{{ __('app.nav.workspaces') }}</span>
                    </a>
                @endif
                @if($currentOwner->hasFeature('booking'))
                    <a href="/bookings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('bookings*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ __('app.nav.bookings') }}</span>
                    </a>
                    <a href="/shared-sessions" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('shared-sessions*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>{{ __('app.nav.shared_sessions') }}</span>
                    </a>
                @endif
                <a href="/settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/10 transition {{ request()->is('settings*') ? 'bg-white/10 border-l-4 border-blue-500' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    </svg>
                    <span>{{ __('app.nav.settings') }}</span>
                </a>
            </nav>
            <div class="px-4 py-4 border-t border-white/10">
                <a href="/profile" class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition mb-2 {{ request()->is('profile') ? 'text-blue-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>{{ __('app.nav.my_profile') }}</span>
                </a>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="text-xs text-red-400 hover:text-red-300 mt-1">{{ __('app.nav.logout') }}</button>
                </form>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white shadow-sm px-4 lg:px-6 py-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', __('app.nav.dashboard'))</h1>
                <div class="flex items-center gap-3">
                    <!-- Notification bell -->
                    <div class="relative" id="notif-wrap">
                        <button type="button" onclick="toggleNotif()" aria-label="{{ __('app.notif.title') }}"
                                class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition focus:outline-none">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if(($navUnreadCount ?? 0) > 0)
                                <span class="absolute top-1 {{ $isRtl ? 'left-1' : 'right-1' }} inline-flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">
                                    {{ $navUnreadCount > 9 ? '9+' : $navUnreadCount }}
                                </span>
                            @endif
                        </button>

                        <div id="notif-dropdown"
                             class="hidden absolute {{ $isRtl ? 'left-0' : 'right-0' }} mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-lg border border-gray-100 z-30 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <span class="text-sm font-semibold text-gray-800">{{ __('app.notif.title') }}</span>
                                @if(($navUnreadCount ?? 0) > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">{{ __('app.notif.mark_all_read') }}</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                                @forelse($navRecentNotifications ?? [] as $n)
                                    @php $c = $n->levelColor(); @endphp
                                    <a href="{{ route('notifications.open', $n->id) }}"
                                       class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition {{ $n->isRead() ? '' : 'bg-blue-50/40' }}">
                                        <span class="mt-0.5 shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-{{ $c }}-100 text-{{ $c }}-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $n->iconPath() }}"/>
                                            </svg>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-medium text-gray-800 truncate">{{ $n->title }}</span>
                                            @if($n->body)
                                                <span class="block text-xs text-gray-500 line-clamp-2">{{ $n->body }}</span>
                                            @endif
                                            <span class="block text-[11px] text-gray-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</span>
                                        </span>
                                        @unless($n->isRead())
                                            <span class="mt-1 shrink-0 w-2 h-2 rounded-full bg-blue-500"></span>
                                        @endunless
                                    </a>
                                @empty
                                    <div class="px-4 py-8 text-center text-sm text-gray-400">{{ __('app.notif.empty') }}</div>
                                @endforelse
                            </div>
                            <a href="{{ route('notifications.index') }}" class="block px-4 py-3 text-center text-sm font-medium text-blue-600 hover:bg-gray-50 border-t border-gray-100">
                                {{ __('app.notif.view_all') }}
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('language.switch', $isRtl ? 'en' : 'ar') }}" class="flex items-center gap-1.5">
                        @csrf
                        <button type="submit" class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out focus:outline-none {{ $isRtl ? 'bg-indigo-600' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $isRtl ? 'true' : 'false' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition duration-200 ease-in-out {{ $isRtl ? 'translate-x-[18px]' : 'translate-x-[3px]' }}"></span>
                        </button>
                        <span class="text-xs font-medium {{ $isRtl ? 'text-indigo-600' : 'text-gray-500' }}">{{ $isRtl ? 'AR' : 'EN' }}</span>
                    </form>
                    <span class="text-sm text-gray-500 truncate">{{ $owner->business_name }}</span>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                @if(auth('owner')->user()->subscriptionStatus() === 'expiring_soon')
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 flex items-center gap-3">
                        <svg class="w-5 h-5 text-yellow-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <p class="text-yellow-800 text-sm font-medium">
                            {{ __('app.msg.subscription_expires_in', ['days' => auth('owner')->user()->daysUntilExpiry()]) }}
                        </p>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    function toggleNotif() {
        document.getElementById('notif-dropdown').classList.toggle('hidden');
    }
    document.addEventListener('click', function (e) {
        const wrap = document.getElementById('notif-wrap');
        const dropdown = document.getElementById('notif-dropdown');
        if (wrap && dropdown && !wrap.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    function closeSidebar() {
        document.getElementById('sidebar').classList.add('{{ $isRtl ? "translate-x-full" : "-translate-x-full" }}');
        document.getElementById('sidebar-overlay').classList.add('hidden');
    }
    document.getElementById('menu-toggle').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('{{ $isRtl ? "translate-x-full" : "-translate-x-full" }}');
        overlay.classList.toggle('hidden');
    });
    </script>
</body>
</html>
