<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    /**
     * Grant access when the owner has ANY of the given feature keys.
     * Single-key usage (`feature:hotspot`) is unchanged; multi-key
     * (`feature:hotspot,booking`) means "any of these".
     */
    public function handle(Request $request, Closure $next, string ...$featureKeys): Response
    {
        $owner = auth('owner')->user();

        if (!$owner) {
            return redirect()->route('login');
        }

        foreach ($featureKeys as $key) {
            if ($owner->hasFeature($key)) {
                return $next($request);
            }
        }

        return redirect('/dashboard')->with(
            'error',
            'You do not have access to this feature. Please contact your administrator.'
        );
    }
}
