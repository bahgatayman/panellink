@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Welcome, {{ $owner->business_name }}</h1>

    @if ($mikrotikError)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
            MikroTik unreachable — live stats unavailable
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm font-medium text-gray-500">Total Users</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ $totalUsers }}</p>
                </div>
                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm font-medium text-gray-500">Active Users</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ $activeUsers }}</p>
                </div>
                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm font-medium text-gray-500">Online Now</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ $activeSessions }}</p>
                    <p class="text-xs text-gray-400 mt-1">Live from MikroTik</p>
                </div>
                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm font-medium text-gray-500">Speed Profiles</p>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900 mt-1">{{ $totalProfiles }}</p>
                </div>
                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <h2 class="text-lg font-semibold text-gray-700 mb-4">Quick Links</h2>
    <div class="flex flex-col sm:flex-row flex-wrap gap-3 lg:gap-4">
        <a href="/users/create" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm text-center">
            Add New User
        </a>
        <a href="/sessions" class="bg-purple-600 text-white px-5 py-2.5 rounded-lg hover:bg-purple-700 transition text-sm font-medium shadow-sm text-center">
            View Active Sessions
        </a>
        <a href="/speed-profiles" class="bg-orange-600 text-white px-5 py-2.5 rounded-lg hover:bg-orange-700 transition text-sm font-medium shadow-sm text-center">
            Manage Speed Profiles
        </a>
    </div>
@endsection
