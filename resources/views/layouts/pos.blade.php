<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="POS Bordados">
    <meta name="application-name" content="POS Bordados">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="msapplication-TileColor" content="#1a1a2e">

    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- PWA Icons -->
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/pos-icon-192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/pos-icon-512.png') }}">

    <title>POS - {{ config('app.name', 'Sistema Bordados') }}</title>

    <!-- Fonts: Inter (Modern, Clean) -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">

    {{-- POS usa CSS propio inline - no requiere Tailwind ni Vite --}}

    <style>
        /* =====================================================================
           POS PREMIUM DESIGN SYSTEM - Apple/SaaS Style
           ===================================================================== */

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            font-size: 15px;
            font-weight: 400;
            line-height: 1.5;
            color: #1e293b;
            background-color: #f1f5f9;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow: hidden;
        }

        /* Main Container */
        .pos-app {
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            background-color: #f1f5f9;
        }

        /* Hidden utility */
        .hidden {
            display: none !important;
        }

        /* SVG Reset */
        svg {
            flex-shrink: 0;
        }

        /* Section label reusable */
        .pos-section-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .pos-section-icon {
            width: 18px;
            height: 18px;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="pos-app">
        @yield('content')
    </div>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful');
                }).catch(function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
