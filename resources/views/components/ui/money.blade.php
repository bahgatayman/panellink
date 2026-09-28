{{-- Money, column-aligned. EN: "EGP 1,948.00" · AR: "1,948.00 ج.م" (matches LS.money in panel.js). --}}
@props(['amount' => 0])
@php $n = number_format((float) $amount, 2); @endphp
<span {{ $attributes->merge(['class' => 'ls-num']) }}>{{ app()->getLocale() === 'ar' ? $n.' ج.م' : 'EGP '.$n }}</span>
