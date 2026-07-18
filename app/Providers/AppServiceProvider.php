<?php

namespace App\Providers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (Auth::guard('owner')->check()) {
                $view->with('owner', Auth::guard('owner')->user());
            }
        });

        // Notification bell — inject unread count + recent items into the owner layout.
        // Alerts are (re)generated at most once every 10 minutes per owner so page
        // loads stay cheap even without the scheduler running.
        View::composer('layouts.app', function ($view) {
            $owner = Auth::guard('owner')->user();
            if (! $owner) {
                return;
            }

            if (Cache::add("notif_refresh_{$owner->id}", true, now()->addMinutes(10))) {
                try {
                    app(NotificationService::class)->refreshForOwner($owner);
                } catch (Throwable $e) {
                    // Never let alert generation break a page render.
                    report($e);
                }
            }

            $view->with('navUnreadCount', Notification::forOwner($owner->id)->unread()->count());
            $view->with('navRecentNotifications',
                Notification::forOwner($owner->id)->latest()->take(6)->get());
        });
    }
}
