@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Settings</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">MikroTik Connection</h2>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
                <dt class="text-sm text-gray-500">Host</dt>
                <dd class="text-sm font-medium text-gray-800">{{ $owner->mikrotik_host }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Port</dt>
                <dd class="text-sm font-medium text-gray-800">{{ $owner->mikrotik_port }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Username</dt>
                <dd class="text-sm font-medium text-gray-800">{{ $owner->mikrotik_username }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Password</dt>
                <dd class="text-sm font-medium text-gray-800">••••••••</dd>
            </div>
        </dl>

        <form method="POST" action="/settings/test-connection">
            @csrf
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition text-sm font-medium">
                Test Connection
            </button>
        </form>
    </div>
@endsection
