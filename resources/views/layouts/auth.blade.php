<!DOCTYPE html>
@php $locale = app()->getLocale(); $isRtl = $locale === 'ar'; @endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('app.auth.login')) · Link Space Panel</title>
    <link rel="icon" type="image/webp" href="/logo.webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                colors: { surface: { 50:'#fafaf9',100:'#f5f5f4',200:'#e7e5e4',300:'#d6d3d1',400:'#a8a29e',500:'#78716c',600:'#57534e',700:'#44403c',800:'#292524',900:'#1c1917' } },
            } },
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        @if($isRtl)
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');
        @endif
        body { font-family: 'Inter', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        @if($isRtl) body { font-family: 'Cairo', 'Inter', system-ui, sans-serif; } @endif

        .field {
            width: 100%; border: 1px solid #e2e4ee; border-radius: 0.65rem;
            padding: 0.65rem 0.85rem; font-size: 0.9rem; color: #1c1917; background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .field::placeholder { color: #b3b0ab; }
        .field:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.14); }
        .lbl { display: block; font-size: 0.8rem; font-weight: 600; color: #44403c; margin-bottom: 0.4rem; }
        .group-lbl { font-size: 0.68rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: #a8a29e; }

        .btn-primary {
            width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: 0.72rem 1rem; border-radius: 0.7rem; font-weight: 600; font-size: 0.9rem;
            color: #fff; background: linear-gradient(135deg, #4f46e5 0%, #6d28d9 100%);
            box-shadow: 0 10px 22px -10px rgba(79,70,229,.55); border: none; cursor: pointer;
            transition: box-shadow .2s, transform .2s;
        }
        .btn-primary:hover { box-shadow: 0 14px 28px -10px rgba(79,70,229,.65); transform: translateY(-1px); }
        .btn-primary svg { transition: transform .2s; }
        .btn-primary:hover svg { transform: translateX(3px); }

        .brand-panel { background: linear-gradient(150deg, #4338ca 0%, #5b21b6 55%, #7c3aed 100%); }
        .brand-grid {
            background-image:
                linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
            background-size: 46px 46px;
            -webkit-mask-image: radial-gradient(120% 100% at 30% 0%, #000 40%, transparent 85%);
                    mask-image: radial-gradient(120% 100% at 30% 0%, #000 40%, transparent 85%);
        }
        .auth-fade { animation: authIn .6s cubic-bezier(.16,1,.3,1) both; }
        @keyframes authIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .auth-fade { animation: none; } }
    </style>
</head>
<body class="min-h-screen bg-surface-50 text-surface-900">
    <div class="min-h-screen grid lg:grid-cols-[1.05fr_1fr]">

        <!-- Brand panel (desktop) -->
        <div class="brand-panel relative hidden lg:flex flex-col justify-between overflow-hidden p-12 xl:p-16 text-white">
            <div class="brand-grid absolute inset-0"></div>
            <div class="absolute -top-24 -left-16 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-28 -right-12 w-[30rem] h-[30rem] rounded-full bg-fuchsia-400/20 blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <img src="/logo.webp" alt="Link Space Panel" class="h-9 w-auto brightness-0 invert">
            </div>

            <div class="relative z-10 max-w-md">
                <h2 class="text-3xl xl:text-4xl font-extrabold leading-[1.15] tracking-tight mb-8" style="text-wrap:balance">{{ __('app.auth.brand_headline') }}</h2>
                <ul class="space-y-4">
                    @foreach(['brand_point_1', 'brand_point_2', 'brand_point_3'] as $pt)
                        <li class="flex items-center gap-3">
                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/15 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-indigo-50/90 text-sm">{{ __('app.auth.'.$pt) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative z-10 text-xs text-indigo-200/70">&copy; {{ date('Y') }} Link Space Panel</div>
        </div>

        <!-- Form panel -->
        <div class="flex flex-col px-6 py-8 sm:px-10 lg:px-14">
            <div class="flex items-center justify-between gap-4">
                <img src="/logo.webp" alt="Link Space Panel" class="h-9 w-auto lg:hidden">
                <form method="POST" action="{{ route('language.switch', $isRtl ? 'en' : 'ar') }}" class="flex items-center gap-1.5 ms-auto">
                    @csrf
                    <button type="submit" class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none {{ $isRtl ? 'bg-indigo-600' : 'bg-surface-300' }}" role="switch" aria-checked="{{ $isRtl ? 'true' : 'false' }}">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition duration-200 {{ $isRtl ? 'translate-x-[18px]' : 'translate-x-[3px]' }}"></span>
                    </button>
                    <span class="text-xs font-medium {{ $isRtl ? 'text-indigo-600' : 'text-surface-500' }}">{{ $isRtl ? 'AR' : 'EN' }}</span>
                </form>
            </div>

            <div class="flex-1 flex items-center justify-center py-10">
                <div class="w-full @yield('formWidth', 'max-w-md') auth-fade">
                    <div class="mb-8">
                        <h1 class="text-2xl sm:text-[1.7rem] font-bold tracking-tight text-surface-900">@yield('heading')</h1>
                        <p class="text-sm text-surface-500 mt-1.5">@yield('subheading')</p>
                    </div>
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.pw-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.parentElement.querySelector('input');
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.querySelectorAll('svg').forEach(s => s.classList.toggle('hidden'));
            });
        });
    </script>
</body>
</html>
