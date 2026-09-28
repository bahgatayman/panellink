{{--
    Session flash → feedback. Success/info become quiet toasts; warnings and errors
    stay on the page as banners so they can't be missed.
--}}
@if (session('success'))<span hidden data-ls-flash="{{ session('success') }}" data-ls-tone="ok"></span>@endif
@if (session('info'))<span hidden data-ls-flash="{{ session('info') }}" data-ls-tone="info"></span>@endif
@if (session('warning'))
    <x-ui.banner tone="warn">{{ session('warning') }}</x-ui.banner>
@endif
@if (session('error'))
    <x-ui.banner tone="danger">{{ session('error') }}</x-ui.banner>
@endif
