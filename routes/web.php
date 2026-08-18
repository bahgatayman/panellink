<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionRequestController;
use App\Http\Controllers\Admin\WorkspaceController as AdminWorkspaceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\HotspotUserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SharedSessionController;
use App\Http\Controllers\SpeedProfileController;
use App\Http\Controllers\SubscriptionController as OwnerSubscriptionController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::post('/demo-request', [DemoRequestController::class, 'send'])->name('demo.request');

Route::post('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// ===================== Owner Auth =====================
Route::middleware('guest:owner')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegister']);
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('owner.logout');

// ===================== Subscription (reachable while expired) =====================
// Deliberately outside `subscription.active`: an owner whose subscription lapsed
// must still be able to see the plans and ask to renew.
Route::middleware('auth:owner')->group(function () {
    Route::get('/subscription/expired', [OwnerSubscriptionController::class, 'expired'])->name('subscription.expired');
    Route::get('/subscription/plans', [OwnerSubscriptionController::class, 'plans'])->name('subscription.plans');
    Route::post('/subscription/request', [OwnerSubscriptionController::class, 'requestRenewal'])->name('subscription.request');
    Route::delete('/subscription/request/{id}', [OwnerSubscriptionController::class, 'cancelRequest'])->name('subscription.request.cancel');
});

// ===================== Owner Routes (authenticated + subscription check) =====================
Route::middleware(['auth:owner', 'subscription.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/users/search', [HotspotUserController::class, 'search']);

    // Member/customer registry — HotspotUser is the shared customer entity that
    // BOOKING owners also need. Available with either feature; router sync is
    // applied inside the controller only for hotspot owners.
    Route::middleware('feature:hotspot,booking')->group(function () {
        Route::get('/users', [HotspotUserController::class, 'index']);
        Route::get('/users/create', [HotspotUserController::class, 'create']);
        Route::post('/users', [HotspotUserController::class, 'store']);
        // Inline "add member" used by the booking + shared-session pickers.
        Route::post('/users/quick', [HotspotUserController::class, 'quickStore']);
        Route::get('/users/{id}', [HotspotUserController::class, 'show']);
        Route::get('/users/{id}/edit', [HotspotUserController::class, 'edit']);
        Route::put('/users/{id}', [HotspotUserController::class, 'update']);
        Route::delete('/users/{id}', [HotspotUserController::class, 'destroy']);
        Route::post('/users/{id}/toggle-status', [HotspotUserController::class, 'toggleStatus']);
    });

    // Hotspot-only: router-backed actions.
    Route::middleware('feature:hotspot')->group(function () {
        Route::post('/users/{id}/speed', [HotspotUserController::class, 'updateSpeed']);
        Route::get('/speed-profiles', [SpeedProfileController::class, 'index']);
        Route::get('/speed-profiles/create', [SpeedProfileController::class, 'create']);
        Route::post('/speed-profiles', [SpeedProfileController::class, 'store']);
        Route::get('/speed-profiles/{id}/edit', [SpeedProfileController::class, 'edit']);
        Route::put('/speed-profiles/{id}', [SpeedProfileController::class, 'update']);
        Route::delete('/speed-profiles/{id}', [SpeedProfileController::class, 'destroy']);
        Route::post('/speed-profiles/{id}/set-default', [SpeedProfileController::class, 'setDefault']);
        Route::get('/sessions', [SessionController::class, 'index']);
    });

    // Workspace feature routes
    Route::middleware('feature:workspace')->group(function () {
        Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::get('/workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
        Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
        Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::get('/workspaces/{workspace}/edit', [WorkspaceController::class, 'edit'])->name('workspaces.edit');
        Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
        Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
        Route::post('/workspaces/{workspace}/toggle', [WorkspaceController::class, 'toggleActive'])->name('workspaces.toggle');

        // Nested room routes
        Route::get('/workspaces/{workspace}/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
        Route::post('/workspaces/{workspace}/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/workspaces/{workspace}/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/workspaces/{workspace}/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/workspaces/{workspace}/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::post('/workspaces/{workspace}/rooms/{room}/toggle', [RoomController::class, 'toggleAvailable'])->name('rooms.toggle');
    });

    // Booking feature routes
    Route::middleware('feature:booking')->group(function () {
        Route::get('/shared-sessions', [SharedSessionController::class, 'index'])->name('shared-sessions.index');
        Route::get('/shared-sessions/create', [SharedSessionController::class, 'create'])->name('shared-sessions.create');
        Route::post('/shared-sessions', [SharedSessionController::class, 'store'])->name('shared-sessions.store');
        Route::get('/shared-sessions/{session}/close-preview', [SharedSessionController::class, 'closePreview'])->name('shared-sessions.close-preview');
        Route::post('/shared-sessions/{session}/close', [SharedSessionController::class, 'close'])->name('shared-sessions.close');

        // Running-tab items on an open session need BOTH booking and sales features.
        Route::middleware('feature:sales')->group(function () {
            Route::post('/shared-sessions/{session}/items', [SharedSessionController::class, 'addItem'])->name('shared-sessions.items.add');
            Route::delete('/shared-sessions/{session}/items/{item}', [SharedSessionController::class, 'removeItem'])->name('shared-sessions.items.remove');
        });

        Route::get('/bookings/calendar', [BookingController::class, 'calendar']);
        Route::get('/bookings/availability', [BookingController::class, 'availabilityLookup']);
        Route::get('/bookings/check-availability', [BookingController::class, 'checkAvailability']);
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/create', [BookingController::class, 'create']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit']);
        Route::put('/bookings/{booking}', [BookingController::class, 'update']);
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
        Route::post('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
        Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn']);

        // Attaching products to a booking needs BOTH booking and sales features.
        Route::middleware('feature:sales')->group(function () {
            Route::post('/bookings/{booking}/items', [BookingController::class, 'addItem'])->name('bookings.items.add');
            Route::delete('/bookings/{booking}/items/{item}', [BookingController::class, 'removeItem'])->name('bookings.items.remove');
        });
    });

    // Sales feature routes (product catalog + sales history)
    Route::middleware('feature:sales')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/toggle', [ProductController::class, 'toggleActive'])->name('products.toggle');

        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    });

    Route::get('/settings', [SettingsController::class, 'index']);
    // Router configuration is only writable/testable when the hotspot feature is on.
    Route::middleware('feature:hotspot')->group(function () {
        Route::post('/settings', [SettingsController::class, 'update']);
        Route::post('/settings/test-connection', [SettingsController::class, 'testConnection']);
    });
    // Working hours are relevant to workspace/booking owners, not hotspot-only ones.
    Route::middleware('feature:workspace,booking')->group(function () {
        Route::post('/settings/working-hours', [SettingsController::class, 'updateWorkingHours'])->name('settings.working-hours.update');
    });

    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/profile/logo', [ProfileController::class, 'updateLogo'])->name('profile.logo.update');
    Route::delete('/profile/logo', [ProfileController::class, 'destroyLogo'])->name('profile.logo.destroy');

    // Notifications (cross-cutting — available to every authenticated owner)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// ===================== Admin Auth =====================
// Unified sign-in: admins use the same /login form as owners. This route only
// exists to redirect legacy /admin/login links (and guest redirects) there.
Route::get('/admin/login', fn () => redirect()->route('login'))->name('admin.login');

Route::post('/admin/logout', [AuthController::class, 'logout']);

// ===================== Admin Routes (authenticated) =====================
Route::middleware('auth:admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    Route::get('/owners', [OwnerController::class, 'index']);
    Route::get('/owners/create', [OwnerController::class, 'create']);
    Route::post('/owners', [OwnerController::class, 'store']);
    Route::get('/owners/{owner}', [OwnerController::class, 'show']);
    Route::put('/owners/{owner}/toggle-active', [OwnerController::class, 'toggleActive']);
    Route::get('/owners/{owner}/users', [OwnerController::class, 'users']);

    Route::post('/owners/{owner}/renew', [SubscriptionController::class, 'renew']);

    // Owner-initiated renewal requests
    Route::get('/subscription-requests', [SubscriptionRequestController::class, 'index'])->name('admin.subscription-requests.index');
    Route::post('/subscription-requests/{id}/approve', [SubscriptionRequestController::class, 'approve'])->name('admin.subscription-requests.approve');
    Route::post('/subscription-requests/{id}/reject', [SubscriptionRequestController::class, 'reject'])->name('admin.subscription-requests.reject');

    // Admin → Owner notifications (broadcast)
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('admin.notifications.store');

    // Admin Feature Management
    Route::get('/features', [FeatureController::class, 'index']);
    Route::post('/features/{feature}/toggle-global', [FeatureController::class, 'toggleGlobal']);
    Route::post('/owners/{owner}/features/{feature}/toggle', [FeatureController::class, 'toggleForOwner']);

    // Admin Workspace (read-only)
    Route::get('/workspaces', [AdminWorkspaceController::class, 'index'])->name('admin.workspaces.index');
    Route::get('/workspaces/{workspace}', [AdminWorkspaceController::class, 'show'])->name('admin.workspaces.show');

    // Admin Bookings (read-only)
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('admin.bookings.show');

    // Admin Plans Management
    Route::get('/plans', [PlanController::class, 'index'])->name('admin.plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('admin.plans.create');
    Route::post('/plans', [PlanController::class, 'store'])->name('admin.plans.store');
    Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('admin.plans.edit');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('admin.plans.update');
    Route::post('/plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('admin.plans.toggle');

    // Admin Financial Dashboard
    Route::get('/financial', [FinancialController::class, 'index'])->name('admin.financial.index');
});

// A truly unmatched path has no route to attach the 'web' middleware group
// to, so SetLocale (and the session) never runs and the 404 page falls back
// to the default locale regardless of the visitor's actual session. Routing
// it through a real (fallback) route fixes that — same 404 response, but
// now locale-aware.
Route::fallback(fn () => abort(404));
