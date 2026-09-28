<?php

namespace App\Support;

/** Same EN/AR money format as <x-ui.money> (public/js/panel.js's LS.money mirrors it client-side). */
final class Money
{
    public static function format(float $amount): string
    {
        $formatted = number_format($amount, 2);

        return app()->getLocale() === 'ar' ? "{$formatted} ج.م" : "EGP {$formatted}";
    }
}
