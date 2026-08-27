<?php

use App\Http\Middleware\CheckFeature;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckStaffActive;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            SetLocale::class,
        ]);

        $middleware->alias([
            'subscription.active' => CheckSubscription::class,
            'feature' => CheckFeature::class,
            'staff.active' => CheckStaffActive::class,
            'permission' => CheckPermission::class,
        ]);

        // Single sign-in page for every guard.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
