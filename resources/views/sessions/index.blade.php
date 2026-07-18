@extends('layouts.app')

@section('page-title', __('app.section.sessions'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.section.sessions') }}</h1>
        <a href="/sessions" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm font-medium">
            {{ __('app.btn.refresh') }}
        </a>
    </div>

    <p class="text-sm text-gray-500 mb-4">Last updated: {{ now()->format('H:i:s') }}</p>

    @if ($error)
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            {{ __('app.msg.mikrotik_connection_error', ['message' => $error]) }}<br>
            <a href="/settings" class="underline font-medium">{{ __('app.msg.check_mikrotik_settings') }}</a>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <p class="text-sm font-medium text-gray-700">{{ count($sessions) }} user(s) currently online</p>
    </div>

    @if (count($sessions) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('app.table.th.name') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.phone') }} / {{ __('app.auth.username') }}</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Uptime</th>
                        <th class="px-4 py-3">Downloaded</th>
                        <th class="px-4 py-3">Uploaded</th>
                        <th class="px-4 py-3">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($sessions as $session)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $session['name'] }}</td>
                            <td class="px-4 py-3">{{ $session['phone'] ?? $session['username'] ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $session['ip'] ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $session['uptime'] ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $session['bytes_in'] ? number_format($session['bytes_in'] / 1048576, 2) . ' MB' : '-' }}</td>
                            <td class="px-4 py-3">{{ $session['bytes_out'] ? number_format($session['bytes_out'] / 1048576, 2) . ' MB' : '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($session['user_id'])
                                    <a href="/users/{{ $session['user_id'] }}" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.common.view') }}</a>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif (!$error)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <p class="text-gray-500 text-lg">{{ __('app.empty.no_active_sessions') }}</p>
        </div>
    @endif
@endsection
