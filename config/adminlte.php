<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'Sistema de Bordados',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<b>SIS</b> Bordados',
    'logo_img' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Admin Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => false,
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        [
            'text' => 'Dashboard',
            'url' => 'home',
            'icon' => 'fas fa-fw fa-tachometer-alt',
            'active' => ['home'],
        ],

        /*
        |--------------------------------------------------------------------------
        | VENTAS POS (Mostrador)
        |--------------------------------------------------------------------------
        */
        ['header' => 'VENTAS POS'],

        [
            'text' => 'Punto de Venta',
            'url' => 'pos',
            'icon' => 'fas fa-fw fa-cash-register',
            'active' => ['pos', 'pos/'],
        ],

        [
            'text' => 'Historial Ventas POS',
            'url' => 'admin/pos-sales',
            'icon' => 'fas fa-fw fa-history',
            'active' => ['admin/pos-sales*'],
        ],

        [
            'text' => 'Ventas Consolidadas',
            'url' => 'admin/sales',
            'icon' => 'fas fa-fw fa-chart-line',
            'icon_color' => 'primary',
            'active' => ['admin/sales*'],
        ],

        /*
        |--------------------------------------------------------------------------
        | PEDIDOS CLIENTE
        |--------------------------------------------------------------------------
        */
        ['header' => 'PEDIDOS CLIENTE'],

        [
            'text' => 'Pedidos',
            'url' => 'admin/orders',
            'icon' => 'fas fa-fw fa-clipboard-list',
            'active' => ['admin/orders*'],
            'label' => 'Nuevo',
            'label_color' => 'success',
        ],

        /*
        |--------------------------------------------------------------------------
        | PRODUCCIÓN OPERATIVA
        |--------------------------------------------------------------------------
        */
        ['header' => 'PRODUCCIÓN OPERATIVA'],

        [
            'text' => 'Cola de Producción',
            'url' => 'admin/production/queue',
            'icon' => 'fas fa-fw fa-industry',
            'icon_color' => 'danger',
            'active' => ['admin/production/queue*'],
            'label_color' => 'warning',
        ],
        [
            'text' => 'Calendario',
            'url' => 'admin/production/calendar',
            'icon' => 'fas fa-fw fa-calendar-alt',
            'icon_color' => 'info',
            'active' => ['admin/production/calendar*'],
        ],

        /*
        |--------------------------------------------------------------------------
        | PRODUCCIÓN TÉCNICA (Bordados)
        |--------------------------------------------------------------------------
        */
        ['header' => 'PRODUCCIÓN TÉCNICA'],

        [
            'text' => 'Diseños de Bordado',
            'icon' => 'fas fa-fw fa-pencil-ruler',
            'active' => ['admin/designs*', 'admin/production', 'admin/production/create*', 'admin/visualizer*'],
            'submenu' => [
                [
                    'text' => 'Categorías de Diseño',
                    'url' => 'admin/categories',
                    'icon' => 'fas fa-fw fa-folder-open',
                    'active' => ['admin/categories*'],
                ],
                [
                    'text' => 'Catálogo de Diseños',
                    'url' => 'admin/designs',
                    'icon' => 'fas fa-fw fa-images',
                    'active' => ['admin/designs*'],
                ],
                [
                    'text' => 'Exportaciones Técnicas',
                    'url' => 'admin/production',
                    'icon' => 'fas fa-fw fa-file-export',
                    'active' => ['admin/production', 'admin/production/create*', 'admin/production/{id}*'],
                ],
                [
                    'text' => 'Visualizador (BETA)',
                    'url' => 'admin/visualizer',
                    'icon' => 'fas fa-fw fa-eye',
                    'active' => ['admin/visualizer*'],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | INVENTARIO DE MATERIALES
        |--------------------------------------------------------------------------
        */
        ['header' => 'INVENTARIO MATERIALES'],

        [
            'text' => 'Stock de Materiales',
            'icon' => 'fas fa-fw fa-warehouse',
            'active' => ['admin/inventory*', 'admin/materials*'],
            'submenu' => [
                [
                    'text' => 'Vista General',
                    'url' => 'admin/inventory',
                    'icon' => 'fas fa-fw fa-th-list',
                    'active' => ['admin/inventory'],
                ],
                [
                    'text' => 'Categorías de Material',
                    'url' => 'admin/material-categories',
                    'icon' => 'fas fa-fw fa-layer-group',
                    'active' => ['admin/material-categories*'],
                ],
                [
                    'text' => 'Catálogo de Materiales',
                    'url' => 'admin/materials',
                    'icon' => 'fas fa-fw fa-boxes',
                    'active' => ['admin/materials*'],
                ],
                [
                    'text' => 'Reservas Activas',
                    'url' => 'admin/inventory/reservations',
                    'icon' => 'fas fa-fw fa-lock',
                    'active' => ['admin/inventory/reservations'],
                ],
                [
                    'text' => 'Historial Reservas',
                    'url' => 'admin/inventory/reservations/history',
                    'icon' => 'fas fa-fw fa-history',
                    'active' => ['admin/inventory/reservations/history'],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | INVENTARIO PRODUCTO TERMINADO
        |--------------------------------------------------------------------------
        */
        ['header' => 'INVENTARIO PROD. TERMINADO'],

        [
            'text' => 'Stock Prod. Terminado',
            'url' => 'admin/finished-goods-stock',
            'icon' => 'fas fa-fw fa-boxes',
            'active' => ['admin/finished-goods-stock*'],
        ],

        /*
        |--------------------------------------------------------------------------
        | MERMA / DESPERDICIO
        |--------------------------------------------------------------------------
        */
        ['header' => 'CONTROL DE MERMA'],

        [
            'text' => 'Registro de Mermas',
            'url' => 'admin/waste',
            'icon' => 'fas fa-fw fa-trash-alt',
            'icon_color' => 'danger',
            'active' => ['admin/waste*'],
        ],

        /*
        |--------------------------------------------------------------------------
        | COMPRAS / ABASTECIMIENTO
        |--------------------------------------------------------------------------
        */
        ['header' => 'COMPRAS'],

        [
            'text' => 'Órdenes de Compra',
            'url' => 'admin/purchases',
            'icon' => 'fas fa-fw fa-shopping-cart',
            'active' => ['admin/purchases*'],
        ],
        // BEFORE: Proveedores estaba aquí bajo COMPRAS
        // AFTER: Movido a CATÁLOGOS (después de Clientes)

        /*
        |--------------------------------------------------------------------------
        | CATÁLOGOS (Maestros)
        |--------------------------------------------------------------------------
        */
        ['header' => 'CATÁLOGOS'],

        [
            'text' => 'Clientes',
            'url' => 'clientes',
            'icon' => 'fas fa-fw fa-users',
            'active' => ['clientes*'],
        ],
        // AFTER: Proveedores movido aquí desde COMPRAS
        [
            'text' => 'Proveedores',
            'url' => 'proveedores',
            'icon' => 'fas fa-fw fa-truck',
            'active' => ['proveedores*'],
        ],
        [
            'text' => 'Productos',
            'icon' => 'fas fa-fw fa-box-open',
            'active' => ['admin/products*', 'admin/product-categories*', 'product_extras*'],
            'submenu' => [
                [
                    'text' => 'Categorías de Producto',
                    'url' => 'admin/product-categories',
                    'icon' => 'fas fa-fw fa-tags',
                    'active' => ['admin/product-categories*'],
                ],
                [
                    'text' => 'Catálogo de Productos',
                    'url' => 'admin/products',
                    'icon' => 'fas fa-fw fa-th-list',
                    'active' => ['admin/products*'],
                ],

                [
                    'text' => 'Servicios Extras',
                    'url' => 'product_extras',
                    'icon' => 'fas fa-fw fa-plus-circle',
                    'active' => ['product_extras*'],
                ],
                [
                    'text' => 'Categorías de Extras',
                    'url' => 'extra_categories',
                    'icon' => 'fas fa-fw fa-tags',
                    'active' => ['extra_categories*'],
                ],
            ],
        ],

        /* [
            'text' => 'Conversiones de Material',
            'url' => 'admin/material-conversions',
            'icon' => 'fas fa-fw fa-exchange-alt',
            'active' => ['admin/material-conversions*'],
        ],*/

        [
            'text' => 'Parámetros',
            'icon' => 'fas fa-fw fa-database',
            'active' => ['attributes*', 'attribute-values*', 'estados*', 'giros*', 'recomendaciones*', 'tipos_aplicacion*'],
            'submenu' => [
                [
                    'text' => 'Atributos',
                    'url' => 'attributes',
                    'icon' => 'fas fa-fw fa-sliders-h',
                    'active' => ['attributes*', 'attribute-values*'],
                ],
                [
                    // BEFORE: 'Estados (Ubicación)'
                    'text' => 'Estados Geográficos',
                    'url' => 'estados',
                    'icon' => 'fas fa-fw fa-map-marker-alt',
                    'active' => ['estados*'],
                ],
                [
                    'text' => 'Giros de Proveedor',
                    'url' => 'giros',
                    'icon' => 'fas fa-fw fa-briefcase',
                    'active' => ['giros*'],
                ],
                [
                    // BEFORE: 'Recomendaciones'
                    'text' => 'Fuentes de Referencia',
                    'url' => 'recomendaciones',
                    'icon' => 'fas fa-fw fa-lightbulb',
                    'active' => ['recomendaciones*'],
                ],
                [
                    'text' => 'Tipos de Aplicación',
                    'url' => 'tipos_aplicacion',
                    'icon' => 'fas fa-fw fa-palette',
                    'active' => ['tipos_aplicacion*'],
                ],
                [
                    'text' => 'Motivos de Descuento',
                    'url' => 'motivos-descuento',
                    'icon' => 'fas fa-fw fa-percent',
                    'active' => ['motivos-descuento*'],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | CONFIGURACIÓN DEL SISTEMA
        |--------------------------------------------------------------------------
        */
        ['header' => 'CONFIGURACIÓN'],

        [
            'text' => 'Sistema',
            'icon' => 'fas fa-fw fa-cogs',
            'active' => ['admin/settings*', 'admin/units*', 'admin/niveles-urgencia*'],
            'submenu' => [
                [
                    'text' => 'Configuración General',
                    'url' => 'admin/settings',
                    'icon' => 'fas fa-fw fa-wrench',
                    'active' => ['admin/settings*'],
                ],
                [
                    'text' => 'Niveles de Urgencia',
                    'url' => 'admin/niveles-urgencia',
                    'icon' => 'fas fa-fw fa-clock',
                    'active' => ['admin/niveles-urgencia*'],
                ],
                [
                    'text' => 'Unidades de Medida',
                    'url' => 'admin/units',
                    'icon' => 'fas fa-fw fa-ruler-combined',
                    'active' => ['admin/units*'],
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRACIÓN / AUDITORÍA
        |--------------------------------------------------------------------------
        */
        ['header' => 'ADMINISTRACIÓN'],

        [
            'text' => 'Acceso y Personal',
            'icon' => 'fas fa-fw fa-user-shield',
            'active' => ['admin/staff*', 'admin/users*'],
            'submenu' => [
                [
                    'text' => 'Personal',
                    'url' => 'admin/staff',
                    'icon' => 'fas fa-fw fa-id-badge',
                    'active' => ['admin/staff*'],
                ],
                [
                    'text' => 'Usuarios',
                    'url' => 'admin/users',
                    'icon' => 'fas fa-fw fa-users-cog',
                    'active' => ['admin/users*'],
                ],
            ],
        ],
        [
            'text' => 'Registro de Actividad',
            'url' => 'admin/activity-logs',
            'icon' => 'fas fa-fw fa-history',
            'active' => ['admin/activity-logs*'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/2.4.0/js/dataTables.buttons.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/2.4.0/js/buttons.bootstrap4.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/2.4.0/js/buttons.html5.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/2.4.0/js/buttons.print.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/2.4.0/js/buttons.colVis.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/buttons/2.4.0/css/buttons.bootstrap4.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
                ],
            ],
        ],
        'Select2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
                ],
            ],
        ],
        'Confirmations' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'js/confirmations.js',
                ],
            ],
        ],
        'Notifications' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'js/notifications.js',
                ],
                // echo-notifications.js desactivado - ahora integrado en menu-item-messages.blade.php
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
