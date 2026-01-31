<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ID usuario actual para WebSocket (DEBE estar en HEAD antes de cualquier script) --}}
    @auth
    <script>window.currentUserId = {{ auth()->id() ?? 0 }};</script>
    @endauth

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Title --}}
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    {{-- IFrame Preloader Removal Workaround --}}
    <!-- IFrame Preloader Removal Workaround -->
    <style type="text/css">
        body.iframe-mode .preloader {
            display: none !important;
        }
        /* Preloader fade-out animation */
        .preloader.preloader-hidden {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        /* Failsafe: Force hide after animation */
        .preloader.preloader-force-hide {
            display: none !important;
        }
        /* ================================================
           ESTÁNDAR VISUAL ERP: Texto negro #212529
           Sobreescribe grises claros de Bootstrap/AdminLTE
           ================================================ */
        .text-muted,
        small,
        .small,
        .form-text {
            color: #212529 !important;
        }
        /* Excepción: mantener colores semánticos */
        .text-danger { color: #dc3545 !important; }
        .text-success { color: #28a745 !important; }
        .text-warning { color: #ffc107 !important; }
        .text-info { color: #17a2b8 !important; }
        .text-primary { color: #007bff !important; }
        .text-secondary { color: #6c757d !important; }
        .text-white { color: #ffffff !important; }
    </style>
    <script>
        // Fix for AdminLTE IFrame mode crash when running in an iframe (e.g. Responsive Viewer)
        // This must run BEFORE adminlte.js loads.
        if (!localStorage.getItem('AdminLTE:IFrame:Options')) {
            localStorage.setItem('AdminLTE:IFrame:Options', JSON.stringify({
                autoIframeMode: false,
                autoDarkMode: false
            }));
        }

        // ============================================
        // ROBUST PRELOADER FAILSAFE SYSTEM
        // Prevents preloader from getting "stuck"
        // ============================================
        (function() {
            var PRELOADER_MAX_TIMEOUT = 8000; // 8 seconds max
            var PRELOADER_NORMAL_TIMEOUT = 3000; // 3 seconds normal fallback

            function hidePreloader() {
                var preloader = document.querySelector('.preloader');
                if (preloader && !preloader.classList.contains('preloader-force-hide')) {
                    preloader.classList.add('preloader-hidden');
                    // After transition, force display:none
                    setTimeout(function() {
                        preloader.classList.add('preloader-force-hide');
                    }, 350);
                }
            }

            // FAILSAFE 1: Maximum timeout (absolute limit)
            setTimeout(function() {
                hidePreloader();
                console.warn('[Preloader] Force hidden by max timeout failsafe');
            }, PRELOADER_MAX_TIMEOUT);

            // FAILSAFE 2: Normal timeout after DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    hidePreloader();
                }, PRELOADER_NORMAL_TIMEOUT);
            });

            // FAILSAFE 3: On window load (images, scripts loaded)
            window.addEventListener('load', function() {
                setTimeout(function() {
                    hidePreloader();
                }, 500);
            });

            // FAILSAFE 4: If jQuery available, use its ready
            if (typeof jQuery !== 'undefined') {
                jQuery(function() {
                    setTimeout(function() {
                        hidePreloader();
                    }, 1000);
                });
            }

            // FAILSAFE 5: Visibility change (user returns to tab)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    var preloader = document.querySelector('.preloader');
                    if (preloader && !preloader.classList.contains('preloader-force-hide')) {
                        // If preloader still visible when user returns, hide it
                        setTimeout(hidePreloader, 200);
                    }
                }
            });

            // FAILSAFE 6: Any user interaction forces preloader hide
            ['click', 'keydown', 'touchstart', 'scroll'].forEach(function(evt) {
                document.addEventListener(evt, function handler() {
                    var preloader = document.querySelector('.preloader');
                    if (preloader && !preloader.classList.contains('preloader-force-hide')) {
                        hidePreloader();
                        // Remove listeners after first trigger
                        document.removeEventListener(evt, handler);
                    }
                }, { once: true, passive: true });
            });
        })();
    </script>

    {{-- Custom stylesheets (pre AdminLTE) --}}
    @yield('adminlte_css_pre')

    {{-- Base Stylesheets (depends on Laravel asset bundling tool) --}}
    @if (config('adminlte.enabled_laravel_mix', false))
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @else
        @switch(config('adminlte.laravel_asset_bundling', false))
            @case('mix')
                <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_css_path', 'css/app.css')) }}">
            @break

            @case('vite')
                @vite([config('adminlte.laravel_css_path', 'resources/css/app.css'), config('adminlte.laravel_js_path', 'resources/js/app.js')])
            @break

            @case('vite_js_only')
                @vite(config('adminlte.laravel_js_path', 'resources/js/app.js'))
            @break

            @default
                @php $cacheVersion = config('app.asset_version', '20260125'); @endphp
                <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}?v={{ $cacheVersion }}">
                <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}?v={{ $cacheVersion }}">
                <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}?v={{ $cacheVersion }}">

                @if (config('adminlte.google_fonts.allowed', true))
                    <link rel="stylesheet"
                        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
                @endif
        @endswitch
    @endif

    {{-- Extra Configured Plugins Stylesheets --}}
    @include('adminlte::plugins', ['type' => 'css'])

    {{-- Livewire Styles --}}
    @if (config('adminlte.livewire'))
        @if (intval(app()->version()) >= 7)
            @livewireStyles
        @else
            <livewire:styles />
        @endif
    @endif

    {{-- Custom Stylesheets (post AdminLTE) --}}
    @yield('adminlte_css')

    {{-- Favicon --}}
    @if (config('adminlte.use_ico_only'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
    @elseif(config('adminlte.use_full_favicon'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('favicons/apple-icon-57x57.png') }}">
        <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('favicons/apple-icon-60x60.png') }}">
        <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('favicons/apple-icon-72x72.png') }}">
        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('favicons/apple-icon-76x76.png') }}">
        <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('favicons/apple-icon-114x114.png') }}">
        <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('favicons/apple-icon-120x120.png') }}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('favicons/apple-icon-144x144.png') }}">
        <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('favicons/apple-icon-152x152.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-icon-180x180.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicons/favicon-96x96.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicons/android-icon-192x192.png') }}">
        <link rel="manifest" crossorigin="use-credentials" href="{{ asset('favicons/manifest.json') }}">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="{{ asset('favicons/ms-icon-144x144.png') }}">
    @endif

</head>

<body class="@yield('classes_body')" @yield('body_data')>

    {{-- Body Content --}}
    @yield('body')

    {{-- Base Scripts (depends on Laravel asset bundling tool) --}}
    @if (config('adminlte.enabled_laravel_mix', false))
        <script src="{{ mix(config('adminlte.laravel_mix_js_path', 'js/app.js')) }}"></script>
    @else
        @switch(config('adminlte.laravel_asset_bundling', false))
            @case('mix')
                <script src="{{ mix(config('adminlte.laravel_js_path', 'js/app.js')) }}"></script>
            @break

            @case('vite')
            @case('vite_js_only')
            @break

            @default
                @php $jsVersion = config('app.asset_version', '20260125'); @endphp
                <script src="{{ asset('vendor/jquery/jquery.min.js') }}?v={{ $jsVersion }}"></script>
                <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}?v={{ $jsVersion }}"></script>
                <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}?v={{ $jsVersion }}"></script>
                <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}?v={{ $jsVersion }}"></script>
        @endswitch
    @endif

    {{-- Extra Configured Plugins Scripts --}}
    @include('adminlte::plugins', ['type' => 'js'])

    {{-- Livewire Script --}}
    @if (config('adminlte.livewire'))
        @if (intval(app()->version()) >= 7)
            @livewireScripts
        @else
            <livewire:scripts />
        @endif
    @endif

    {{-- Custom Scripts --}}
    @yield('adminlte_js')

</body>

</html>
