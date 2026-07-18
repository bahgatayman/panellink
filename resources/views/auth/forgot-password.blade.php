<!DOCTYPE html>
@php $locale = app()->getLocale(); $isRtl = $locale === 'ar'; @endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.auth.linkspace') }} - {{ __('app.auth.forgot_password') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if($isRtl)
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
    @endif
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <div class="flex justify-center mb-6">
            <img src="/logo.webp" alt="Link Space Panel" class="h-12 w-auto">
        </div>
        <h1 class="text-2xl font-bold text-gray-900 text-center mb-2">{{ __('app.auth.forgot_password') }}</h1>
        <p class="text-sm text-gray-500 text-center mb-8">{{ __('app.auth.forgot_password_hint') }}</p>

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                <p class="text-sm">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach ($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/forgot-password">
            @csrf
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.auth.email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
                {{ __('app.auth.send_reset_link') }}
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            <a href="/login" class="text-blue-600 font-medium hover:underline">{{ __('app.auth.back_to_login') }}</a>
        </p>
    </div>
</body>
</html>
