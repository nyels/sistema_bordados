@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css')
    @stack('css')
    @yield('css')
    <style>
        /* Universal Premium Modal Close Button (Apple Style) */
        .modal-close-premium {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 45px;
            height: 45px;
            background: #2563eb;
            border: 2px solid #fff;
            border-radius: 50%;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1070;
            padding: 0;
        }

        .modal-close-premium:hover {
            background: #1d4ed8;
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
            color: #fff;
        }

        .modal-close-premium:focus {
            outline: none;
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 576px) {
            .modal-close-premium {
                width: 38px;
                height: 38px;
                top: -10px;
                right: -10px;
                font-size: 16px;
            }
        }

        /* Sidebar Submenu Improvements (Premium Look) */
        .nav-treeview>.nav-item>.nav-link {
            padding-left: 2.2rem;
            /* Más indentación para jerarquía visual */
        }

        /* Más espacio entre icono y texto */
        .nav-sidebar .nav-link p {
            margin-left: 0.3rem;
        }

        /* Submenú activo: Fondo sutil y borde indicador */
        .nav-treeview>.nav-item>.nav-link.active,
        .nav-treeview>.nav-item>.nav-link.active:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            border-left: 4px solid #3b82f6;
            /* Azul premium */
            color: #fff !important;
        }

        /* Efecto hover en items no activos */
        .nav-sidebar .nav-item>.nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* ============================================
                           SIDEBAR SECTION HEADERS WITH DIVIDERS
                           ============================================ */
        .nav-sidebar .nav-header {
            padding: 0.8rem 1rem 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #9ca3af;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.5rem;
            margin-top: 0.8rem;
        }

        /* Primer header sin margen superior */
        .nav-sidebar .nav-header:first-child {
            margin-top: 0;
        }

        /* Efecto hover en headers */
        .nav-sidebar .nav-header:hover {
            color: #d1d5db;
        }
    </style>
@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="wrapper">

        {{-- Preloader Animation (fullscreen mode) --}}
        @if ($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if ($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Main Sidebar --}}
        @if (!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        @empty($iFrameEnabled)
            @include('adminlte::partials.cwrapper.cwrapper-default')
        @else
            @include('adminlte::partials.cwrapper.cwrapper-iframe')
        @endempty

        {{-- Footer --}}
        @hasSection('footer')
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Control Sidebar --}}
        @if ($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
    {{-- Bloque para ÉXITO --}}
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: "{{ session('success') }}",
                timer: 3500,
                showConfirmButton: false,
                customClass: {
                    popup: 'fade-in'
                }
            });
        </script>
    @endif

    {{-- Bloque para ERROR --}}
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: "{{ session('error') }}",
                showConfirmButton: true,
                confirmButtonColor: '#2563eb',
                customClass: {
                    popup: 'shake',
                    confirmButton: 'btn-premium-primary'
                }
            });
        </script>
    @endif

    {{-- Sidebar Auto-Scroll al expandir menús --}}
    <script>
        $(function() {
            $('[data-widget="treeview"]').on('expanded.lte.treeview', function() {
                setTimeout(function() {
                    var $lastOpened = $('.nav-sidebar .menu-open').last();

                    if ($lastOpened.length) {
                        var $lastItem = $lastOpened.find('> .nav-treeview > .nav-item').last();

                        if ($lastItem.length) {
                            $lastItem[0].scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });
                        }
                    }
                }, 100);
            });

            // GLOBAL: Prevenir doble envío en formularios (Store/Update)
            $('form').on('submit', function() {
                var $form = $(this);

                // Excluir formularios GET (búsquedas) o con target blank (exportaciones)
                if ($form.attr('method').toUpperCase() === 'GET' || $form.attr('target') === '_blank') {
                    return;
                }

                // Excluir si tiene clase especifica 'no-loader'
                if ($form.hasClass('no-loader')) return;

                // Validación HTML5
                if (!this.checkValidity()) return;

                var $submitBtn = $form.find('button[type="submit"]');

                // Si no hay botón submit, buscar input submit o button en general
                if ($submitBtn.length === 0) {
                    $submitBtn = $form.find('input[type="submit"]');
                }

                if ($submitBtn.length > 0) {
                    $submitBtn.each(function() {
                        var $btn = $(this);
                        // Dimesiona para evitar que cambie el tamaño
                        $btn.css('width', $btn.outerWidth());

                        $btn.prop('disabled', true);

                        // Buscar icono y cambiar a spinner
                        var $icon = $btn.find('i');
                        if ($icon.length > 0) {
                            $icon.data('original-class', $icon.attr(
                            'class')); // Guardar clase original
                            $icon.removeClass().addClass('fas fa-spinner fa-spin');
                        } else {
                            $btn.prepend('<i class="fas fa-spinner fa-spin mr-1"></i> ');
                        }
                    });

                    // Desactivar botones de cancelar/regresar hermanos
                    $submitBtn.siblings('a, button').addClass('disabled').css('pointer-events', 'none');
                    // Tambien el padre si es un grupo
                    $submitBtn.parent().find('a, button').not($submitBtn).addClass('disabled').css(
                        'pointer-events', 'none');
                }
            });
        });
    </script>
@stop
