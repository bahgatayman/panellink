<!DOCTYPE html>
@php $locale = app()->getLocale(); $isRtl = $locale === 'ar'; @endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.auth.linkspace') }} - {{ __('app.auth.register') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if($isRtl)
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
    @endif
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg p-8">
        <div class="flex justify-center mb-4">
            <img src="/logo.png" alt="Link Space Panel" class="h-24 w-auto">
        </div>
        <p class="text-sm text-gray-500 text-center mb-8">{{ __('app.auth.create_your_account') }}</p>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach ($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/register">
            @csrf

            <h2 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">{{ __('app.auth.account_information') }}</h2>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.business_name') }}</label>
                    <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.password') }}</label>
                    <input type="password" name="password" id="password" required minlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <h2 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">{{ __('app.auth.mikrotik_connection') }}</h2>
            <div class="mb-4">
                <label for="mikrotik_host" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.router_ip') }}</label>
                <input type="text" name="mikrotik_host" id="mikrotik_host" value="{{ old('mikrotik_host') }}" required
                       placeholder="192.168.88.1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="mikrotik_port" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.api_port') }}</label>
                    <input type="number" name="mikrotik_port" id="mikrotik_port" value="{{ old('mikrotik_port', 8728) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="mikrotik_username" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_username') }}</label>
                    <input type="text" name="mikrotik_username" id="mikrotik_username" value="{{ old('mikrotik_username') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="mikrotik_password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.label.mikrotik_password') }}</label>
                    <input type="password" name="mikrotik_password" id="mikrotik_password" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
                {{ __('app.auth.create_account') }}
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            {{ __('app.auth.already_have_account') }}
            <a href="/login" class="text-blue-600 font-medium hover:underline">{{ __('app.auth.login') }}</a>
        </p>
    </div>
</body>
</html>
