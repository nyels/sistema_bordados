<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA / Web App Meta Tags --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Sistema Bordados') }}">
    <meta name="theme-color" content="#343a40">
    <meta name="format-detection" content="telephone=no">

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

        /* SweetAlert2: Prevenir scroll en móviles/tablets */
        body.swal2-shown:not(.swal2-toast-shown) {
            overflow: hidden !important;
            position: fixed !important;
            width: 100% !important;
        }
        .swal2-popup {
            font-size: 14px !important;
        }
        /* Toast container: position fixed para que NO afecte scroll */
        .swal2-container.swal2-top-end.swal2-backdrop-show,
        .swal2-container.swal2-top-end.swal2-noanimation {
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            overflow: visible !important;
        }
        body.swal2-toast-shown .swal2-container {
            position: fixed !important;
            pointer-events: none;
        }
        body.swal2-toast-shown .swal2-container .swal2-toast {
            pointer-events: auto;
        }
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

            // ============================================
            // GLOBAL OVERLAY CLEANUP
            // Limpia overlays huérfanos tras sesión expirada,
            // suspensión de equipo, o bfcache del navegador.
            // ============================================
            function cleanOrphanOverlays() {
                // 1. Preloader atascado
                hidePreloader();

                // 2. Modal backdrops de Bootstrap
                document.querySelectorAll('.modal-backdrop').forEach(function(el) {
                    el.remove();
                });

                // 3. SweetAlert2 overlays y body lock
                document.querySelectorAll('.swal2-container').forEach(function(el) {
                    el.remove();
                });

                // 4. Limpiar clases del body que bloquean interacción
                document.body.classList.remove(
                    'modal-open', 'swal2-shown', 'swal2-toast-shown', 'swal2-height-auto'
                );
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                if (document.body.style.position === 'fixed') {
                    document.body.style.position = '';
                    document.body.style.width = '';
                }
            }

            // Ejecutar al cargar (carga normal)
            document.addEventListener('DOMContentLoaded', cleanOrphanOverlays);

            // Ejecutar en pageshow (bfcache — navegador restaura página del cache)
            window.addEventListener('pageshow', function(e) {
                if (e.persisted) {
                    cleanOrphanOverlays();
                }
            });

            // Ejecutar cuando el usuario regresa a la pestaña (resume de suspensión)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Esperar un frame para que el DOM se estabilice
                    requestAnimationFrame(cleanOrphanOverlays);
                }
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

    {{-- SweetAlert2 Global Configuration: Prevent scroll issues on mobile --}}
    <script>
    if (typeof Swal !== 'undefined') {
        const originalSwalFire = Swal.fire;
        Swal.fire = function(options) {
            // Agregar configuración por defecto para evitar scroll
            if (typeof options === 'object' && options !== null) {
                options.scrollbarPadding = options.scrollbarPadding !== undefined ? options.scrollbarPadding : false;
                options.heightAuto = options.heightAuto !== undefined ? options.heightAuto : false;
            }
            return originalSwalFire.apply(this, arguments);
        };
        // Mantener los métodos estáticos
        Object.keys(originalSwalFire).forEach(function(key) {
            Swal.fire[key] = originalSwalFire[key];
        });
    }
    </script>

    {{-- ================================================================
         GLOBAL: Bloquear entrada de valores negativos en inputs numéricos
         Aplica a TODOS los input[type="number"] con min >= 0
         ================================================================ --}}
    <script>
    (function() {
        'use strict';

        /**
         * Bloquea teclas no permitidas en inputs numéricos positivos
         * - Bloquea: - (menos), e, E (notación científica)
         * - Permite: números, punto decimal, backspace, delete, tab, arrows, home, end
         */
        function blockNegativeInput(event) {
            // Teclas bloqueadas para inputs positivos
            if (event.key === '-' || event.key === 'e' || event.key === 'E') {
                event.preventDefault();
                return false;
            }
            return true;
        }

        /**
         * Bloquea pegado de valores negativos
         */
        function blockNegativePaste(event) {
            var clipboardData = event.clipboardData || window.clipboardData;
            var pastedData = clipboardData.getData('text');
            // Si el valor pegado contiene un signo negativo, bloquear
            if (pastedData && pastedData.indexOf('-') !== -1) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        /**
         * Valida y corrige valor negativo al cambiar (blur/change)
         */
        function validatePositiveValue(event) {
            var input = event.target;
            var value = parseFloat(input.value);
            if (!isNaN(value) && value < 0) {
                input.value = Math.abs(value);
            }
        }

        /**
         * Aplica protección a un input numérico
         */
        function protectNumericInput(input) {
            // Solo aplicar si tiene min >= 0 (no acepta negativos)
            var minAttr = input.getAttribute('min');
            var minValue = minAttr !== null ? parseFloat(minAttr) : null;

            // Si no tiene min definido o min >= 0, aplicar protección
            if (minValue === null || minValue >= 0) {
                // Evitar doble aplicación
                if (input.dataset.numericProtected) return;
                input.dataset.numericProtected = 'true';

                // Agregar event listeners
                input.addEventListener('keydown', blockNegativeInput);
                input.addEventListener('paste', blockNegativePaste);
                input.addEventListener('blur', validatePositiveValue);
                input.addEventListener('change', validatePositiveValue);
            }
        }

        /**
         * Inicializa protección en todos los inputs numéricos existentes
         */
        function initNumericProtection() {
            var inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(protectNumericInput);
        }

        // Ejecutar al cargar DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initNumericProtection);
        } else {
            initNumericProtection();
        }

        // Observer para inputs dinámicos (modales, AJAX, etc.)
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Si es un input numérico
                        if (node.tagName === 'INPUT' && node.type === 'number') {
                            protectNumericInput(node);
                        }
                        // Si contiene inputs numéricos
                        if (node.querySelectorAll) {
                            var inputs = node.querySelectorAll('input[type="number"]');
                            inputs.forEach(protectNumericInput);
                        }
                    }
                });
            });
        });

        // Observar cambios en el DOM
        observer.observe(document.body || document.documentElement, {
            childList: true,
            subtree: true
        });

        // Exponer función para uso manual si es necesario
        window.protectNumericInput = protectNumericInput;
        window.initNumericProtection = initNumericProtection;
    })();
    </script>

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
