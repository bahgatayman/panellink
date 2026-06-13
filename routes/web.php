<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HotspotUserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpeedProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest:owner')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegister']);
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout']);

Route::middleware('auth:owner')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/users', [HotspotUserController::class, 'index']);
    Route::get('/users/create', [HotspotUserController::class, 'create']);
    Route::post('/users', [HotspotUserController::class, 'store']);
    Route::get('/users/{id}', [HotspotUserController::class, 'show']);
    Route::get('/users/{id}/edit', [HotspotUserController::class, 'edit']);
    Route::put('/users/{id}', [HotspotUserController::class, 'update']);
    Route::delete('/users/{id}', [HotspotUserController::class, 'destroy']);
    Route::post('/users/{id}/toggle-status', [HotspotUserController::class, 'toggleStatus']);
    Route::post('/users/{id}/speed', [HotspotUserController::class, 'updateSpeed']);

    Route::get('/speed-profiles', [SpeedProfileController::class, 'index']);
    Route::get('/speed-profiles/create', [SpeedProfileController::class, 'create']);
    Route::post('/speed-profiles', [SpeedProfileController::class, 'store']);
    Route::get('/speed-profiles/{id}/edit', [SpeedProfileController::class, 'edit']);
    Route::put('/speed-profiles/{id}', [SpeedProfileController::class, 'update']);
    Route::delete('/speed-profiles/{id}', [SpeedProfileController::class, 'destroy']);
    Route::post('/speed-profiles/{id}/set-default', [SpeedProfileController::class, 'setDefault']);

    Route::get('/sessions', [SessionController::class, 'index']);

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::post('/settings/test-connection', [SettingsController::class, 'testConnection']);
});
