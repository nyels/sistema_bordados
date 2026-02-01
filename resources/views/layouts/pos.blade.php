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
    <meta name="theme-color" content="#0f172a">
    <meta name="msapplication-TileColor" content="#0f172a">

    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <!-- PWA Icons -->
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/pos-icon-192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/pos-icon-512.png') }}">

    <title>POS - {{ config('app.name', 'Sistema Bordados') }}</title>

    <!-- Fonts: Inter (Modern, Clean) -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:300,400,500,600,700,800,900" rel="stylesheet">

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* =====================================================================
           POS ENTERPRISE DESIGN SYSTEM 2025 - Modern SaaS Style
           ===================================================================== */

        :root {
            /* Primary Colors - Slate/Indigo Theme */
            --pos-primary: #4f46e5;
            --pos-primary-dark: #4338ca;
            --pos-primary-light: #6366f1;

            /* Neutral Colors */
            --pos-slate-900: #0f172a;
            --pos-slate-800: #1e293b;
            --pos-slate-700: #334155;
            --pos-slate-600: #475569;
            --pos-slate-500: #64748b;
            --pos-slate-400: #94a3b8;
            --pos-slate-300: #cbd5e1;
            --pos-slate-200: #e2e8f0;
            --pos-slate-100: #f1f5f9;
            --pos-slate-50: #f8fafc;
            --pos-white: #ffffff;

            /* Accent Colors */
            --pos-success: #10b981;
            --pos-success-dark: #059669;
            --pos-warning: #f59e0b;
            --pos-danger: #ef4444;
            --pos-danger-dark: #dc2626;
            --pos-info: #3b82f6;

            /* Spacing */
            --pos-space-xs: 4px;
            --pos-space-sm: 8px;
            --pos-space-md: 16px;
            --pos-space-lg: 24px;
            --pos-space-xl: 32px;
            --pos-space-2xl: 48px;

            /* Border Radius */
            --pos-radius-sm: 8px;
            --pos-radius-md: 12px;
            --pos-radius-lg: 16px;
            --pos-radius-xl: 20px;
            --pos-radius-full: 9999px;

            /* Shadows */
            --pos-shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --pos-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --pos-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --pos-shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);

            /* Transitions */
            --pos-transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

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
            color: var(--pos-slate-800);
            background-color: var(--pos-slate-900);
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
            background-color: var(--pos-slate-900);
        }

        /* Hidden utility */
        .hidden {
            display: none !important;
        }

        /* SVG Reset */
        svg {
            flex-shrink: 0;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--pos-slate-400);
            border-radius: var(--pos-radius-full);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--pos-slate-500);
        }

        /* Button Reset */
        button {
            font-family: inherit;
            cursor: pointer;
        }

        /* Input Reset */
        input, textarea, select {
            font-family: inherit;
        }

        /* Focus visible outline */
        :focus-visible {
            outline: 2px solid var(--pos-primary);
            outline-offset: 2px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="pos-app">
        @yield('content')
    </div>

    <!-- SweetAlert2 for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
