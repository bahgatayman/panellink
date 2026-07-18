<!DOCTYPE html>
@php $locale = app()->getLocale(); $isRtl = $locale === 'ar'; @endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkSpace — {{ __('app.landing.product_demo') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        surface: { 50: '#fafaf9', 100: '#f5f5f4', 200: '#e7e5e4', 300: '#d6d3d1', 400: '#a8a29e', 500: '#78716c', 600: '#57534e', 700: '#44403c', 800: '#292524', 900: '#1c1917' },
                    },
                },
            },
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        @if($isRtl)
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');
        @endif

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #fafaf9;
            color: #1c1917;
            -webkit-font-smoothing: antialiased;
        }

        @if($isRtl)
        body { font-family: 'Cairo', 'Inter', system-ui, sans-serif; }
        @endif

        .text-gradient {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 50%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .fade-in { opacity: 0; transform: translateY(20px); transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .fade-in.visible { opacity: 1; transform: translateY(0); }

        .product-screen {
            background: white;
            border-radius: 16px;
            border: 1px solid #e7e5e4;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .product-screen:hover {
            box-shadow: 0 12px 40px -12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .screen-tab {
            padding: 10px 16px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #78716c;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: none;
        }
        .screen-tab:hover { color: #1c1917; background: #f5f5f4; }
        .screen-tab.active { color: #4338ca; background: #eef2ff; }

        .screen-panel { display: none; }
        .screen-panel.active { display: block; }

        .mockup-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            transition: background 0.15s;
        }
        .mockup-row:hover { background: #f5f5f4; }

        .mockup-stat {
            padding: 16px;
            border-radius: 12px;
            background: #fafaf9;
        }

        .flow-step {
            position: relative;
            padding: 32px;
            border-radius: 16px;
            background: white;
            border: 1px solid #e7e5e4;
            transition: all 0.3s;
        }
        .flow-step:hover { border-color: #c7d2fe; box-shadow: 0 8px 32px -8px rgba(67, 56, 202, 0.08); }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            border: none;
        }
        .cta-primary {
            background: #4338ca;
            color: white;
            box-shadow: 0 4px 16px -4px rgba(67, 56, 202, 0.25);
        }
        .cta-primary:hover {
            background: #4338ca;
            box-shadow: 0 8px 24px -4px rgba(67, 56, 202, 0.3);
            transform: translateY(-1px);
        }
        .cta-secondary {
            background: white;
            color: #1c1917;
            border: 1px solid #e7e5e4;
        }
        .cta-secondary:hover { border-color: #c7d2fe; box-shadow: 0 4px 16px -4px rgba(0,0,0,0.06); transform: translateY(-1px); }

        .testimonial-card {
            background: white;
            border: 1px solid #e7e5e4;
            border-radius: 16px;
            padding: 28px;
            transition: all 0.3s;
        }
        .testimonial-card:hover { border-color: #e0e7ff; box-shadow: 0 8px 24px -8px rgba(67, 56, 202, 0.06); }

        .pricing-card {
            background: white;
            border: 1px solid #e7e5e4;
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s;
        }
        .pricing-card:hover { border-color: #c7d2fe; box-shadow: 0 12px 32px -8px rgba(67, 56, 202, 0.08); }
        .pricing-card.featured {
            border-color: #4338ca;
            box-shadow: 0 8px 32px -8px rgba(67, 56, 202, 0.12);
        }

        .modal-overlay { background: rgba(28, 25, 23, 0.5); backdrop-filter: blur(8px); }

        @media (max-width: 640px) {
            .cta-btn { padding: 12px 22px; font-size: 0.85rem; }
        }
    </style>
</head>
<body>

    <!-- ═══════════════════════════════════════════
         NAVIGATION
         ═══════════════════════════════════════════ -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-surface-100">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center gap-3 shrink-0">
                    <img src="https://www.link-space.net/img/logo%20link%20space.png"
                         alt="LinkSpace"
                         class="h-7 w-auto">
                </a>

                <div class="hidden sm:flex items-center gap-8">
                    <a href="#product" class="text-sm font-medium text-surface-500 hover:text-surface-900 transition">{{ __('app.landing.product') }}</a>
                    <a href="#how-it-works" class="text-sm font-medium text-surface-500 hover:text-surface-900 transition">{{ __('app.landing.how_it_works') }}</a>
                    <a href="#pricing" class="text-sm font-medium text-surface-500 hover:text-surface-900 transition">{{ __('app.landing.pricing') }}</a>
                    <a href="/login" class="text-sm font-medium text-surface-500 hover:text-surface-900 transition">{{ __('app.landing.sign_in') }}</a>
                    <button onclick="openDemoModal()" class="cta-primary !py-2.5 !px-5 text-sm">{{ __('app.landing.get_started') }}</button>
                    <form method="POST" action="{{ route('language.switch', $isRtl ? 'en' : 'ar') }}" class="flex items-center gap-1.5 ml-2">
                        @csrf
                        <button type="submit" class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 ease-in-out focus:outline-none {{ $isRtl ? 'bg-indigo-600' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $isRtl ? 'true' : 'false' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition duration-200 ease-in-out {{ $isRtl ? 'translate-x-[18px]' : 'translate-x-[3px]' }}"></span>
                        </button>
                        <span class="text-xs font-medium {{ $isRtl ? 'text-indigo-600' : 'text-surface-500' }}">{{ $isRtl ? 'AR' : 'EN' }}</span>
                    </form>
                </div>

                <button class="sm:hidden p-2 text-surface-600" onclick="toggleMobile()">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden sm:hidden border-t border-surface-100 bg-white">
            <div class="px-5 py-4 space-y-3">
                <a href="#product" class="block text-sm font-medium text-surface-600 py-2">{{ __('app.landing.product') }}</a>
                <a href="#how-it-works" class="block text-sm font-medium text-surface-600 py-2">{{ __('app.landing.how_it_works') }}</a>
                <a href="#pricing" class="block text-sm font-medium text-surface-600 py-2">{{ __('app.landing.pricing') }}</a>
                <a href="/login" class="block text-sm font-medium text-brand-600 py-2">{{ __('app.landing.sign_in') }}</a>
                <button onclick="openDemoModal(); toggleMobile();" class="w-full cta-primary justify-center text-sm">{{ __('app.landing.get_started') }}</button>
            </div>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════════
         IDENTITY SCREEN
         ═══════════════════════════════════════════ -->
    <section class="min-h-screen flex items-center justify-center pt-16 px-5 sm:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <div class="fade-in">
                <div class="inline-flex items-center gap-2 bg-surface-100 rounded-full px-4 py-1.5 mb-8">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    <span class="text-xs font-medium text-surface-600">{{ __('app.landing.now_with_smart_booking') }}</span>
                </div>
            </div>

            <h1 class="text-[clamp(2.5rem,6vw,4.5rem)] font-extrabold tracking-tight leading-[1.08] mb-6 fade-in" style="transition-delay:0.05s">
                <span class="text-surface-900">{{ __('app.landing.run_your_space') }}</span>
                <br>
                <span class="text-gradient">{{ __('app.landing.from_one_place') }}</span>
            </h1>

            <p class="text-base sm:text-lg text-surface-500 max-w-lg mx-auto leading-relaxed mb-10 fade-in" style="transition-delay:0.1s">
                {{ __('app.landing.hero_description') }}
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center mb-16 fade-in" style="transition-delay:0.15s">
                <button onclick="openDemoModal()" class="cta-primary justify-center">
                    {{ __('app.landing.start_free_trial') }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
                <button onclick="document.getElementById('product').scrollIntoView({behavior:'smooth'})" class="cta-secondary justify-center">
                    {{ __('app.landing.see_how_it_works') }}
                </button>
            </div>

            <div class="flex flex-wrap justify-center gap-x-8 gap-y-3 text-sm text-surface-400 fade-in" style="transition-delay:0.2s">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ __('app.landing.no_cli_needed') }}
                </span>
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ __('app.landing.real_time_sync') }}
                </span>
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ __('app.landing.five_minute_setup') }}
                </span>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         PRODUCT EXPERIENCE — Interactive Flow
         ═══════════════════════════════════════════ -->
    <section id="product" class="py-20 sm:py-28 px-5 sm:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-4">{{ __('app.landing.product_experience') }}</span>
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-surface-900">{{ __('app.landing.explore_the_system') }}</h2>
                <p class="mt-3 text-surface-500 text-base sm:text-lg">Click through each module to see how LinkSpace works.</p>
            </div>

            <div class="product-screen fade-in">
                <!-- Screen Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-surface-100">
                    <div class="flex items-center gap-2">
                        <img src="https://www.link-space.net/img/logo%20link%20space.png"
                             alt="LinkSpace"
                             class="h-5 w-auto">
                        <span class="text-xs font-medium text-surface-400 hidden sm:inline">·</span>
                        <span class="text-xs font-medium text-surface-400 hidden sm:inline">{{ __('app.landing.product_demo') }}</span>
                    </div>
                    <div class="flex items-center gap-1 bg-surface-50 rounded-lg p-1">
                        <button class="screen-tab active" onclick="switchTab(0)">{{ __('app.landing.wifi_tab') }}</button>
                        <button class="screen-tab" onclick="switchTab(1)">{{ __('app.landing.rooms_tab') }}</button>
                        <button class="screen-tab" onclick="switchTab(2)">{{ __('app.landing.bookings_tab') }}</button>
                    </div>
                </div>

                <!-- Screen Content -->
                <div class="p-5 sm:p-8">
                    <!-- Tab 0: Wi-Fi Management -->
                    <div class="screen-panel active" id="tab-0">
                        <div class="grid sm:grid-cols-5 gap-6">
                            <div class="sm:col-span-3">
                                <div class="bg-surface-50 rounded-2xl p-5 sm:p-6">
                                    <!-- Mockup: Hotspot Users List -->
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-semibold text-surface-900">Hotspot Users</h4>
                                        <span class="text-[0.65rem] font-medium text-surface-400 bg-white px-2.5 py-1 rounded-md border border-surface-200">128 active</span>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="mockup-row bg-white border border-surface-200">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-[0.6rem] font-bold text-indigo-700">AA</div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-surface-900 truncate">Ahmed Ali</p>
                                                <p class="text-[0.65rem] text-surface-400">+20 100 000 0001</p>
                                            </div>
                                            <span class="text-[0.6rem] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
                                        </div>
                                        <div class="mockup-row bg-white border border-surface-200">
                                            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-[0.6rem] font-bold text-purple-700">SM</div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-surface-900 truncate">Sara Mahmoud</p>
                                                <p class="text-[0.65rem] text-surface-400">+20 101 000 0002</p>
                                            </div>
                                            <span class="text-[0.6rem] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
                                        </div>
                                        <div class="mockup-row bg-white border border-surface-200">
                                            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-[0.6rem] font-bold text-amber-700">MK</div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-surface-900 truncate">Mohamed Khaled</p>
                                                <p class="text-[0.65rem] text-surface-400">+20 102 000 0003</p>
                                            </div>
                                            <span class="text-[0.6rem] font-medium text-surface-400 bg-surface-100 px-2 py-0.5 rounded-full">Offline</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-2 space-y-4">
                                <div class="mockup-stat">
                                    <p class="text-[0.65rem] font-medium text-surface-400 uppercase tracking-wider">Active Sessions</p>
                                    <p class="text-2xl font-bold text-surface-900 mt-1">24</p>
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <span class="flex items-center gap-1 text-[0.6rem] text-emerald-600 font-medium">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                            +12%
                                        </span>
                                        <span class="text-[0.6rem] text-surface-400">vs last week</span>
                                    </div>
                                </div>
                                <div class="mockup-stat">
                                    <p class="text-[0.65rem] font-medium text-surface-400 uppercase tracking-wider">Bandwidth Usage</p>
                                    <p class="text-2xl font-bold text-surface-900 mt-1">240 <span class="text-sm font-normal text-surface-400">Mbps</span></p>
                                    <div class="mt-3 h-1.5 bg-surface-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" style="width:24%"></div>
                                    </div>
                                </div>
                                <div class="mockup-stat">
                                    <p class="text-[0.65rem] font-medium text-surface-400 uppercase tracking-wider">Speed Profile</p>
                                    <p class="text-lg font-bold text-surface-900 mt-1">50 Mbps</p>
                                    <p class="text-[0.65rem] text-surface-400 mt-0.5">Standard Plan</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 1: Room Management -->
                    <div class="screen-panel" id="tab-1">
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="bg-surface-50 rounded-xl p-5 border border-surface-100 hover:border-indigo-200 transition">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    </div>
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                </div>
                                <h4 class="text-sm font-semibold text-surface-900">Meeting Room A</h4>
                                <p class="text-[0.7rem] text-surface-400 mt-1">Capacity: 8 · ج.م 150/hr</p>
                                <div class="mt-3 pt-3 border-t border-surface-200">
                                    <p class="text-[0.65rem] text-surface-500">Next: <span class="font-medium text-surface-700">10:00 - 12:00</span></p>
                                </div>
                            </div>
                            <div class="bg-surface-50 rounded-xl p-5 border border-surface-100 hover:border-indigo-200 transition">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                                </div>
                                <h4 class="text-sm font-semibold text-surface-900">Private Office B</h4>
                                <p class="text-[0.7rem] text-surface-400 mt-1">Capacity: 4 · ج.م 200/hr</p>
                                <div class="mt-3 pt-3 border-t border-surface-200">
                                    <p class="text-[0.65rem] text-surface-500">Now: <span class="font-medium text-emerald-600">Sara M. · Active</span></p>
                                </div>
                            </div>
                            <div class="bg-surface-50 rounded-xl p-5 border border-surface-100 hover:border-indigo-200 transition">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </div>
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                </div>
                                <h4 class="text-sm font-semibold text-surface-900">Hot Desk Area</h4>
                                <p class="text-[0.7rem] text-surface-400 mt-1">Capacity: 20 · ج.م 50/hr</p>
                                <div class="mt-3 pt-3 border-t border-surface-200">
                                    <p class="text-[0.65rem] text-surface-500">Available: <span class="font-medium text-emerald-600">12 seats open</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-5 bg-surface-50 rounded-xl">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-semibold text-surface-900">Today's Room Schedule</h4>
                                <span class="text-[0.65rem] text-surface-400">3 rooms · 8 bookings</span>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-4 p-3 bg-white rounded-lg border border-surface-200">
                                    <span class="text-xs font-mono text-surface-500 w-20 shrink-0">10:00-12:00</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-surface-900">Meeting Room A</p>
                                        <p class="text-[0.65rem] text-surface-400">Ahmed Ali</p>
                                    </div>
                                    <span class="text-[0.6rem] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Confirmed</span>
                                </div>
                                <div class="flex items-center gap-4 p-3 bg-white rounded-lg border border-surface-200">
                                    <span class="text-xs font-mono text-surface-500 w-20 shrink-0">14:00-16:00</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-surface-900">Private Office B</p>
                                        <p class="text-[0.65rem] text-surface-400">Sara Mahmoud</p>
                                    </div>
                                    <span class="text-[0.6rem] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
                                </div>
                                <div class="flex items-center gap-4 p-3 bg-white rounded-lg border border-surface-200">
                                    <span class="text-xs font-mono text-surface-500 w-20 shrink-0">16:00-18:00</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-surface-900">Hot Desk Area</p>
                                        <p class="text-[0.65rem] text-surface-400">Omar Youssef</p>
                                    </div>
                                    <span class="text-[0.6rem] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Booking Engine -->
                    <div class="screen-panel" id="tab-2">
                        <div class="grid sm:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-semibold text-surface-900 mb-4">New Booking</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-[0.7rem] font-medium text-surface-500 mb-1">Customer</label>
                                        <div class="w-full bg-white border border-surface-200 rounded-lg px-3 py-2.5 text-sm text-surface-900">Ahmed Ali</div>
                                    </div>
                                    <div>
                                        <label class="block text-[0.7rem] font-medium text-surface-500 mb-1">Room</label>
                                        <div class="w-full bg-white border border-surface-200 rounded-lg px-3 py-2.5 text-sm text-surface-900">Meeting Room A</div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[0.7rem] font-medium text-surface-500 mb-1">Start Time</label>
                                            <div class="bg-white border border-surface-200 rounded-lg px-3 py-2.5 text-sm text-surface-900">10:00</div>
                                        </div>
                                        <div>
                                            <label class="block text-[0.7rem] font-medium text-surface-500 mb-1">End Time</label>
                                            <div class="bg-white border border-surface-200 rounded-lg px-3 py-2.5 text-sm text-surface-900">12:00</div>
                                        </div>
                                    </div>
                                    <div class="pt-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-surface-500">Total</span>
                                            <span class="font-bold text-surface-900">ج.م 300</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-semibold text-surface-900 mb-4">Conflict Prevention</h4>
                                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 mb-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <div>
                                            <p class="text-sm font-medium text-emerald-800">No conflicts found</p>
                                            <p class="text-[0.7rem] text-emerald-600 mt-0.5">Meeting Room A is available for this time slot</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-surface-50 rounded-xl p-4">
                                    <p class="text-[0.7rem] font-medium text-surface-500 mb-3">Today's Availability</p>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-3">
                                            <span class="text-[0.65rem] font-mono text-surface-400 w-16">08:00</span>
                                            <div class="flex-1 h-2 bg-surface-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-emerald-400 rounded-full" style="width:100%"></div>
                                            </div>
                                            <span class="text-[0.6rem] text-surface-400 w-12 text-right">Open</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[0.65rem] font-mono text-surface-400 w-16">10:00</span>
                                            <div class="flex-1 h-2 bg-surface-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-indigo-400 rounded-full" style="width:40%"></div>
                                            </div>
                                            <span class="text-[0.6rem] text-surface-400 w-12 text-right">Booked</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[0.65rem] font-mono text-surface-400 w-16">14:00</span>
                                            <div class="flex-1 h-2 bg-surface-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-amber-400 rounded-full" style="width:30%"></div>
                                            </div>
                                            <span class="text-[0.6rem] text-surface-400 w-12 text-right">Active</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[0.65rem] font-mono text-surface-400 w-16">16:00</span>
                                            <div class="flex-1 h-2 bg-surface-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-emerald-400 rounded-full" style="width:100%"></div>
                                            </div>
                                            <span class="text-[0.6rem] text-surface-400 w-12 text-right">Open</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         HOW IT WORKS — Guided Flow
         ═══════════════════════════════════════════ -->
    <section id="how-it-works" class="py-20 sm:py-28 px-5 sm:px-8 bg-white border-y border-surface-100">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-4">{{ __('app.landing.simple_setup') }}</span>
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-surface-900">{{ __('app.landing.go_live_in_three_steps') }}</h2>
                <p class="mt-3 text-surface-500 text-base sm:text-lg">Connect, configure, manage — in that order.</p>
            </div>

            <div class="grid sm:grid-cols-3 gap-6 fade-in">
                <div class="flow-step">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center mb-5">
                        <span class="text-lg font-bold text-indigo-600">1</span>
                    </div>
                    <h3 class="text-base font-bold text-surface-900 mb-2">{{ __('app.landing.connect_your_router') }}</h3>
                    <p class="text-sm text-surface-500 leading-relaxed">Enter your MikroTik router's IP and credentials. We verify the connection instantly — no configuration needed on your end.</p>
                </div>
                <div class="flow-step">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mb-5">
                        <span class="text-lg font-bold text-purple-600">2</span>
                    </div>
                    <h3 class="text-base font-bold text-surface-900 mb-2">{{ __('app.landing.configure_your_space') }}</h3>
                    <p class="text-sm text-surface-500 leading-relaxed">Define workspaces, rooms, pricing, and speed profiles. Everything is set up visually in your dashboard.</p>
                </div>
                <div class="flow-step">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-5">
                        <span class="text-lg font-bold text-emerald-600">3</span>
                    </div>
                    <h3 class="text-base font-bold text-surface-900 mb-2">{{ __('app.landing.start_managing') }}</h3>
                    <p class="text-sm text-surface-500 leading-relaxed">Take bookings, manage users, monitor live sessions, and grow your coworking business — all from one dashboard.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         DASHBOARD PREVIEW
         ═══════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 px-5 sm:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <div class="fade-in">
                    <span class="inline-block text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-4">Dashboard</span>
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-surface-900 mb-4">{{ __('app.landing.everything_at_a_glance') }}</h2>
                    <p class="text-surface-500 text-base sm:text-lg mb-8 leading-relaxed">Your command center for the entire coworking operation. Real-time data, quick actions, full visibility.</p>

                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-surface-900">Live Usage Stats</p>
                                <p class="text-sm text-surface-400 mt-0.5">Active users, sessions, and bandwidth — all in real-time.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-surface-900">Today's Bookings</p>
                                <p class="text-sm text-surface-400 mt-0.5">Your daily schedule at a glance with status tracking.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-surface-900">Monthly Revenue</p>
                                <p class="text-sm text-surface-400 mt-0.5">Track completed bookings with clear financial overview.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fade-in" style="transition-delay:0.1s">
                    <div class="bg-white rounded-2xl border border-surface-200 p-5 sm:p-7 shadow-lg shadow-surface-200/50">
                        <div class="flex items-center justify-between mb-5 pb-4 border-b border-surface-100">
                            <div>
                                <p class="text-xs text-surface-400 font-medium">Dashboard</p>
                                <p class="text-base font-bold text-surface-900 mt-0.5">Good morning, SpaceHub</p>
                            </div>
                            <span class="text-[0.65rem] font-medium text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">All Systems Go</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div class="bg-surface-50 rounded-xl p-4">
                                <p class="text-[0.65rem] text-surface-400">Active Users</p>
                                <p class="text-xl font-bold text-surface-900 mt-0.5">128</p>
                            </div>
                            <div class="bg-surface-50 rounded-xl p-4">
                                <p class="text-[0.65rem] text-surface-400">Online Now</p>
                                <p class="text-xl font-bold text-indigo-600 mt-0.5">24</p>
                            </div>
                            <div class="bg-surface-50 rounded-xl p-4">
                                <p class="text-[0.65rem] text-surface-400">Today's Bookings</p>
                                <p class="text-xl font-bold text-amber-600 mt-0.5">8</p>
                            </div>
                            <div class="bg-surface-50 rounded-xl p-4">
                                <p class="text-[0.65rem] text-surface-400">Month Revenue</p>
                                <p class="text-xl font-bold text-emerald-600 mt-0.5">ج.م 3,240</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button onclick="openDemoModal()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-center text-sm font-semibold py-3 rounded-xl transition">New Booking</button>
                            <button onclick="openDemoModal()" class="flex-1 bg-surface-100 hover:bg-surface-200 text-surface-700 text-center text-sm font-semibold py-3 rounded-xl transition">View Reports</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         WHY LINKSPACE
         ═══════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 px-5 sm:px-8 bg-surface-900 text-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-xs font-semibold text-indigo-300 bg-white/5 px-3 py-1 rounded-full mb-4">{{ __('app.landing.why_linkspace') }}</span>
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight">{{ __('app.landing.built_for_spaces') }}</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 fade-in">
                <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-7 border border-white/5 hover:bg-white/[0.07] transition">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-base font-bold mb-2">{{ __('app.landing.no_technical_skills') }}</h3>
                    <p class="text-sm text-surface-400 leading-relaxed">Forget CLI commands. LinkSpace connects via API and gives you a beautiful interface to manage everything.</p>
                </div>
                <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-7 border border-white/5 hover:bg-white/[0.07] transition">
                    <div class="w-11 h-11 rounded-xl bg-purple-500/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-base font-bold mb-2">{{ __('app.landing.real_time_everything') }}</h3>
                    <p class="text-sm text-surface-400 leading-relaxed">User changes sync instantly. Live session data, booking updates — no delays, no manual steps.</p>
                </div>
                <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-7 border border-white/5 hover:bg-white/[0.07] transition sm:col-span-2 lg:col-span-1">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-base font-bold mb-2">{{ __('app.landing.secure_multi_tenant') }}</h3>
                    <p class="text-sm text-surface-400 leading-relaxed">Every space is isolated. Your data, customers, and configurations stay private and completely separate.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         PRICING
         ═══════════════════════════════════════════ -->
    <section id="pricing" class="py-20 sm:py-28 px-5 sm:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-4">{{ __('app.landing.pricing') }}</span>
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-surface-900">{{ __('app.landing.simple_transparent_pricing') }}</h2>
                <p class="mt-3 text-surface-500 text-base sm:text-lg">{{ __('app.landing.start_free_scale') }}</p>
            </div>

            <div class="grid sm:grid-cols-3 gap-6 max-w-5xl mx-auto fade-in">
                <div class="pricing-card">
                    <h3 class="text-base font-bold text-surface-900 mb-1">Starter</h3>
                    <p class="text-sm text-surface-400 mb-4">For small spaces</p>
                    <p class="text-4xl font-black text-surface-900 mb-1">Free</p>
                    <p class="text-sm text-surface-400 mb-6">Forever free</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> 50 hotspot users</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> 1 workspace</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> 10 rooms</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Basic support</li>
                    </ul>
                    <button onclick="openDemoModal()" class="w-full cta-secondary justify-center font-semibold">{{ __('app.landing.get_started') }}</button>
                </div>

                <div class="pricing-card featured relative">
                    <span class="absolute -top-3 right-6 text-[0.6rem] font-bold text-white bg-indigo-600 px-3 py-1 rounded-full">Most Popular</span>
                    <h3 class="text-base font-bold text-surface-900 mb-1">Professional</h3>
                    <p class="text-sm text-surface-400 mb-4">For growing spaces</p>
                    <p class="text-4xl font-black text-surface-900 mb-1">ج.م 999<span class="text-base font-medium text-surface-400">/mo</span></p>
                    <p class="text-sm text-surface-400 mb-6">Everything you need</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Unlimited users</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Unlimited workspaces</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Booking engine</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Priority support</li>
                    </ul>
                    <button onclick="openDemoModal()" class="w-full cta-primary justify-center font-semibold">{{ __('app.landing.start_free_trial') }}</button>
                </div>

                <div class="pricing-card">
                    <h3 class="text-base font-bold text-surface-900 mb-1">Enterprise</h3>
                    <p class="text-sm text-surface-400 mb-4">For multi-location</p>
                    <p class="text-4xl font-black text-surface-900 mb-1">Custom</p>
                    <p class="text-sm text-surface-400 mb-6">Contact us</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Everything in Pro</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Multi-location</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Custom integrations</li>
                        <li class="flex items-center gap-2.5 text-sm text-surface-600"><svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Dedicated support</li>
                    </ul>
                    <button onclick="openDemoModal()" class="w-full cta-secondary justify-center font-semibold">Contact Us</button>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         TESTIMONIALS
         ═══════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 px-5 sm:px-8 bg-white border-y border-surface-100">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 fade-in">
                <span class="inline-block text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full mb-4">{{ __('app.landing.testimonials') }}</span>
                <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-surface-900">{{ __('app.landing.loved_by_space_operators') }}</h2>
            </div>

            <div class="grid sm:grid-cols-3 gap-5 fade-in">
                <div class="testimonial-card">
                    <div class="flex items-center gap-0.5 mb-4">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-sm text-surface-600 mb-5 leading-relaxed">"LinkSpace transformed how we manage our coworking space. The booking system alone saved us hours of manual work every week."</p>
                    <div class="flex items-center gap-3 pt-4 border-t border-surface-100">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white text-xs font-bold">AK</div>
                        <div>
                            <p class="text-sm font-semibold text-surface-900">Ahmed Khalid</p>
                            <p class="text-[0.7rem] text-surface-400">SpaceHub Coworking, Cairo</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="flex items-center gap-0.5 mb-4">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-sm text-surface-600 mb-5 leading-relaxed">"The MikroTik integration is flawless. We went from spending hours on user management to doing it all in minutes."</p>
                    <div class="flex items-center gap-3 pt-4 border-t border-surface-100">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-xs font-bold">SM</div>
                        <div>
                            <p class="text-sm font-semibold text-surface-900">Sara Mahmoud</p>
                            <p class="text-[0.7rem] text-surface-400">WorkBase, Alexandria</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="flex items-center gap-0.5 mb-4">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-sm text-surface-600 mb-5 leading-relaxed">"The calendar view and conflict prevention are game-changers. No more double-bookings or scheduling headaches."</p>
                    <div class="flex items-center gap-3 pt-4 border-t border-surface-100">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white text-xs font-bold">OY</div>
                        <div>
                            <p class="text-sm font-semibold text-surface-900">Omar Youssef</p>
                            <p class="text-[0.7rem] text-surface-400">The Desk, Giza</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         FINAL CTA
         ═══════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 px-5 sm:px-8">
        <div class="max-w-3xl mx-auto text-center fade-in">
            <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-surface-900 mb-4">{{ __('app.landing.ready_to_transform') }}</h2>
            <p class="text-surface-500 text-base sm:text-lg mb-8 max-w-xl mx-auto">{{ __('app.landing.join_500_spaces') }}</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="openDemoModal()" class="cta-primary justify-center">
                    {{ __('app.landing.start_free_trial') }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
                <button onclick="openDemoModal()" class="cta-secondary justify-center">
                    {{ __('app.landing.request_demo') }}
                </button>
            </div>
            <p class="text-xs text-surface-400 mt-4">{{ __('app.landing.no_credit_card_needed') }} · 14-day free trial · Cancel anytime</p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         FOOTER
         ═══════════════════════════════════════════ -->
    <footer class="border-t border-surface-200 py-10 px-5 sm:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-5">
                <div class="flex items-center gap-3">
                    <img src="https://www.link-space.net/img/logo%20link%20space.png"
                         alt="LinkSpace"
                         class="h-7 w-auto">
                </div>
                <p class="text-sm text-surface-400 text-center">{{ __('app.landing.footer_text') }}</p>
                <div class="flex items-center gap-5 text-sm text-surface-400">
                    <a href="/login" class="hover:text-surface-900 transition font-medium">{{ __('app.landing.sign_in') }}</a>
                    <a href="/register" class="hover:text-surface-900 transition font-medium">Register</a>
                    <a href="mailto:bahgatayman10@gmail.com" class="hover:text-surface-900 transition font-medium">Contact</a>
                </div>
            </div>
            <div class="mt-6 pt-5 border-t border-surface-100 text-center">
                <p class="text-xs text-surface-400">&copy; {{ date('Y') }} LinkSpace. {{ __('app.landing.all_rights_reserved') }}</p>
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════════
         Flash Message
         ═══════════════════════════════════════════ -->
    @if (session('success'))
        <div id="flash-message" class="fixed top-24 left-1/2 -translate-x-1/2 z-50 max-w-md w-full mx-4">
            <div class="bg-white rounded-2xl shadow-2xl border border-emerald-100 px-6 py-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-surface-800 font-medium">{{ session('success') }}</p>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-surface-400 hover:text-surface-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <script>setTimeout(() => { const el = document.getElementById('flash-message'); if (el) el.remove(); }, 6000);</script>
    @endif

    <!-- ═══════════════════════════════════════════
         Demo Modal
         ═══════════════════════════════════════════ -->
    <div id="demo-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div onclick="closeDemoModal()" class="absolute inset-0 modal-overlay"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
            <button onclick="closeDemoModal()" class="absolute top-4 right-4 text-surface-400 hover:text-surface-600 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-500/20">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-surface-900">{{ __('app.landing.demo_modal_title') }}</h3>
                <p class="text-sm text-surface-500 mt-1">{{ __('app.landing.demo_modal_subtitle') }}</p>
            </div>

            <form method="POST" action="/demo-request" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1.5">{{ __('app.landing.your_full_name') }} *</label>
                    <input type="text" name="name" required placeholder="{{ __('app.placeholder.full_name') }}"
                           class="w-full border border-surface-200 rounded-xl px-4 py-3 text-sm text-surface-900 placeholder-surface-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1.5">{{ __('app.landing.your_email') }} *</label>
                    <input type="email" name="email" required placeholder="{{ __('app.placeholder.email') }}"
                           class="w-full border border-surface-200 rounded-xl px-4 py-3 text-sm text-surface-900 placeholder-surface-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1.5">{{ __('app.landing.your_phone') }}</label>
                    <input type="text" name="phone" placeholder="{{ __('app.placeholder.phone') }}"
                           class="w-full border border-surface-200 rounded-xl px-4 py-3 text-sm text-surface-900 placeholder-surface-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1.5">{{ __('app.landing.company_space_name') }}</label>
                    <input type="text" name="company" placeholder="{{ __('app.placeholder.business_name') }}"
                           class="w-full border border-surface-200 rounded-xl px-4 py-3 text-sm text-surface-900 placeholder-surface-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1.5">{{ __('app.landing.message') }}</label>
                    <textarea name="message" rows="3" placeholder="{{ __('app.placeholder.space_description') }}"
                              class="w-full border border-surface-200 rounded-xl px-4 py-3 text-sm text-surface-900 placeholder-surface-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition"></textarea>
                </div>
                <button type="submit" class="w-full cta-primary justify-center !py-3.5">
                    {{ __('app.landing.send_request') }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         JavaScript
         ═══════════════════════════════════════════ -->
    <script>
        // Tab switching
        function switchTab(index) {
            document.querySelectorAll('.screen-tab').forEach((tab, i) => {
                tab.classList.toggle('active', i === index);
            });
            document.querySelectorAll('.screen-panel').forEach((panel, i) => {
                panel.classList.toggle('active', i === index);
            });
        }

        // Modal
        function openDemoModal() {
            document.getElementById('demo-modal').classList.remove('hidden');
            document.getElementById('demo-modal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeDemoModal() {
            document.getElementById('demo-modal').classList.add('hidden');
            document.getElementById('demo-modal').classList.remove('flex');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDemoModal(); });
        document.addEventListener('click', e => { if (e.target.id === 'demo-modal') closeDemoModal(); });

        // Mobile menu
        function toggleMobile() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        // Scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    </script>
</body>
</html>
