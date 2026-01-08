@extends('adminlte::page')

@section('title', 'PRODUCCIONES')

@section('content_header')
@stop

@section('content')
    <div class="container-fluid py-4">


        {{-- Card Unificada: Filtros + Tabla --}}
        <div class="card card-primary" bis_skin_checked="1">
            <div class="card-header d-flex flex-column align-items-start" bis_skin_checked="1">
                <h3 class="card-title mb-1" style="font-weight: bold; font-size: 20px;">
                    <i class="fas fa-industry text-white mr-2"></i>Gestion de Producciones
                </h3>
            </div>

            <div class="card-body ">
                {{-- Filtros --}}
                <div class="row align-items-end mb-3">
                    {{-- ACCIONES --}}
                    <div class="col-12">
                        <a href="{{ route('admin.designs.index') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Producción
                        </a>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"
                            style="font-weight: 600; font-size: 12px; color: #6b7280; text-transform: uppercase;">Jerarquía</label>
                        <select class="form-control" id="filtroJerarquia">
                            <option value="">Todos los niveles</option>
                            <option value="diseño">Solo Diseños</option>
                            <option value="variante">Solo Variantes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"
                            style="font-weight: 600; font-size: 12px; color: #6b7280; text-transform: uppercase;">Estado
                            Actual</label>
                        <select class="form-control" id="filtroEstado">
                            <option value="">Ver todos los estados</option>
                            <option value="borrador">Borrador</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="archivado">Archivado</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary" id="limpiarFiltros">
                            <i class="fas fa-times-circle mr-1"></i>Limpiar
                        </button>
                    </div>
                </div>

                {{-- Tabla --}}
                <table id="tablaProducciones" class="table table-bordered table-hover ">
                    <thead class="thead-dark text-center">
                        <tr>
                            <th style="font-size: 13px; text-transform: uppercase;">Orden</th>
                            <th style="font-size: 13px; text-transform: uppercase;">Diseño / Linaje</th>
                            <th style="font-size: 13px; text-transform: uppercase;">Vista</th>
                            <th style="font-size: 13px; text-transform: uppercase;">Especificaciones</th>
                            <th style="font-size: 13px; text-transform: uppercase;">Estado</th>
                            <th style="font-size: 13px; text-transform: uppercase;">Última Actividad</th>
                            <th style="font-size: 13px; text-transform: uppercase;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exportaciones as $export)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                {{-- Orden --}}
                                <td style="padding: 16px; vertical-align: middle;">
                                    <a href="#" class="orden-link"
                                        style="font-weight: 700; color: #2563eb; text-decoration: none;">
                                        #PRD-{{ str_pad($export->id, 3, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>

                                {{-- Diseño / Linaje --}}
                                <td style="padding: 16px; vertical-align: middle;">
                                    @if ($export->variant)
                                        <span class="badge"
                                            style="background: #f3e8ff; color: #6b21a8; font-size: 13px; padding: 5px 10px; border-radius: 6px; font-weight: 700; margin-bottom: 6px; display: inline-block;">
                                            VARIANTE
                                        </span>
                                        <div style="font-weight: 700; color: #111827; font-size: 17px;">
                                            {{ $export->variant->name }}
                                        </div>
                                        <div style="font-size: 14px; color: #6b7280; font-weight: 500;">
                                            Padre: {{ $export->design->name ?? 'N/A' }}
                                        </div>
                                    @else
                                        <span class="badge"
                                            style="background: #dbeafe; color: #1e40af; font-size: 13px; padding: 5px 10px; border-radius: 6px; font-weight: 700; margin-bottom: 6px; display: inline-block;">
                                            DISEÑO
                                        </span>
                                        <div style="font-weight: 700; color: #111827; font-size: 17px;">
                                            {{ $export->design->name ?? 'Sin diseño' }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Vista Previa --}}
                                <td style="padding: 16px; vertical-align: middle; text-align: center;">
                                    <div class="preview-wrapper"
                                        style="position: relative; display: inline-block; width: 60px; height: 60px;">
                                        {{-- Skeleton Placeholder --}}
                                        <div class="skeleton-loader"
                                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 8px; background: #f3f4f6;">
                                        </div>

                                        <img data-src="{{ route('admin.production.preview', $export->id) }}" alt="Preview"
                                            class="embroidery-thumbnail lazy-preview" data-id="{{ $export->id }}"
                                            data-name="{{ $export->variant->name ?? ($export->design->name ?? 'Diseño') }}"
                                            style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px; background: #f9fafb; padding: 4px; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); opacity: 0;">
                                    </div>
                                </td>

                                {{-- Especificaciones --}}
                                <td style="padding: 16px; vertical-align: middle;">
                                    <div class="specs-wrapper btn-view-details"
                                        data-url="{{ route('admin.production.show', $export->id) }}?type=specs"
                                        style="font-size: 14px; line-height: 1.6;">
                                        <span class="d-block text-dark"><strong>Puntadas:</strong>
                                            {{ number_format($export->stitches_count) }}</span>
                                        <span class="d-block text-dark"><strong>Colores:</strong>
                                            {{ $export->colors_count ?? 0 }}</span>
                                        <span class="d-block text-muted"><strong>Tamaño:</strong>
                                            {{ $export->width_mm ?? 0 }}x{{ $export->height_mm ?? 0 }}mm</span>
                                    </div>
                                </td>

                                {{-- Estado --}}
                                <td style="padding: 16px; vertical-align: middle;">
                                    @php
                                        $statusColors = [
                                            'borrador' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
                                            'pendiente' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                            'aprobado' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                            'archivado' => ['bg' => '#e5e7eb', 'color' => '#374151'],
                                        ];
                                        $status = $export->status ?? 'borrador';
                                        $colors = $statusColors[$status] ?? $statusColors['borrador'];
                                    @endphp
                                    <span class="badge"
                                        style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; font-size: 13px; padding: 8px 14px; border-radius: 20px; font-weight: 700; text-transform: uppercase;">
                                        {{ $export->translated_status }}
                                    </span>
                                </td>

                                {{-- Última Actividad --}}
                                <td style="padding: 16px; vertical-align: middle;">
                                    <div style="font-weight: 700; color: #111827; font-size: 15px;">
                                        {{ $export->creator->name ?? 'Usuario' }}
                                    </div>
                                    <div style="font-size: 14px; color: #6b7280; font-weight: 500;">
                                        @if ($status == 'pendiente')
                                            Solicitó aprobación -
                                        @elseif($status == 'aprobado')
                                            Aprobó -
                                        @else
                                            Creó {{ $status }} -
                                        @endif
                                        {{ strtoupper($export->updated_at->translatedFormat('d M Y - H:i')) }}
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td style="padding: 12px; vertical-align: middle; text-align: center;">
                                    <div class="d-flex justify-content-center align-items-center" style="gap: 8px;">
                                        {{-- 1. Ver (siempre visible - azul) --}}
                                        <a href="javascript:void(0)" class="btn-action btn-view-details"
                                            data-url="{{ route('admin.production.show', $export->id) }}?type=details"
                                            title="Ver detalles"
                                            style="width: 32px; height: 32px; border-radius: 6px; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                            <i class="fas fa-eye" style="color: #2563eb; font-size: 14px;"></i>
                                        </a>

                                        {{-- 2. Editar / Revertir / Restaurar --}}
                                        @if ($status == 'borrador' || $status == 'pendiente')
                                            {{-- Borrador o Pendiente: Editar disponible --}}
                                            <a href="{{ route('admin.production.edit', $export->id) }}" class="btn-action"
                                                title="Editar"
                                                style="width: 32px; height: 32px; border-radius: 6px; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <i class="fas fa-pen" style="color: #374151; font-size: 13px;"></i>
                                            </a>
                                        @elseif ($status == 'aprobado')
                                            {{-- Aprobado: Revertir a pendiente --}}
                                            <form action="{{ route('admin.production.revert', $export->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-action" title="Revertir a pendiente"
                                                    style="width: 32px; height: 32px; border-radius: 6px; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                    <i class="fas fa-undo" style="color: #374151; font-size: 13px;"></i>
                                                </button>
                                            </form>
                                        @elseif ($status == 'archivado')
                                            {{-- Archivado: Restaurar a aprobado --}}
                                            <form action="{{ route('admin.production.restore', $export->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-action" title="Restaurar a aprobado"
                                                    style="width: 32px; height: 32px; border-radius: 6px; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                    <i class="fas fa-redo" style="color: #374151; font-size: 13px;"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- 3. Botón de estado (CÍRCULO) --}}
                                        @if ($status == 'borrador')
                                            {{-- Borrador: Solicitar aprobación --}}
                                            <form action="{{ route('admin.production.request', $export->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-action" title="Solicitar aprobación"
                                                    style="width: 32px; height: 32px; border-radius: 50%; background: #f59e0b; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: none; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);">
                                                    <i class="fas fa-paper-plane"
                                                        style="color: #fff; font-size: 13px;"></i>
                                                </button>
                                            </form>
                                        @elseif ($status == 'pendiente')
                                            {{-- Pendiente: Aprobar --}}
                                            <form action="{{ route('admin.production.approve', $export->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-action" title="Aprobar"
                                                    style="width: 32px; height: 32px; border-radius: 50%; background: #10b981; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: none; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);">
                                                    <i class="fas fa-check" style="color: #ffffff; font-size: 14px;"></i>
                                                </button>
                                            </form>
                                        @elseif ($status == 'aprobado')
                                            {{-- Aprobado: Archivar --}}
                                            <form action="{{ route('admin.production.archive', $export->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-action" title="Archivar"
                                                    style="width: 32px; height: 32px; border-radius: 50%; background: #666c6a; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: none; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);">
                                                    <i class="fas fa-archive" style="color: #fff; font-size: 13px;"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- 4. Eliminar --}}
                                        <form action="{{ route('admin.production.destroy', $export->id) }}"
                                            method="POST" class="d-inline form-eliminar">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action" title="Eliminar"
                                                style="width: 32px; height: 32px; border-radius: 6px; background: #fff; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; border: 1px solid #fee2e2; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <i class="fas fa-trash-alt" style="color: #ef4444; font-size: 14px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div style="color: #9ca3af;">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0" style="font-size: 16px;">No hay producciones registradas
                                        </p>
                                        <a href="{{ route('admin.production.create') }}" class="btn btn-primary mt-3">
                                            <i class="fas fa-plus mr-1"></i>Crear primera producción
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal de Zoom Avanzado --}}
        <div class="modal fade" id="zoomModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content"
                    style="border-radius: 16px; border: none; overflow: visible; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                    <button type="button" class="modal-close-premium" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header border-0 bg-white" style="padding: 20px 24px;">
                        <h5 class="modal-title" style="font-weight: 700; color: #111827; font-size: 1.25rem;">
                            <i class="fas fa-search-plus text-success mr-2"></i><span id="zoomModalTitle">Vista de Alta
                                Precisión</span>
                        </h5>
                    </div>
                    <div class="modal-body p-0 position-relative"
                        style="background-color: #f8fafc; height: 75vh; min-height: 500px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        {{-- Technical Canvas Background --}}
                        <div
                            style="position: absolute; top:0; left:0; width:100%; height:100%; 
                             background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
                             background-size: 20px 20px; opacity: 0.5; pointer-events: none;">
                        </div>

                        <div id="zoomContainer" class="zoom-container">
                            <div id="zoomWrapper" class="zoom-wrapper">
                                <img id="zoomImage" src="" class="zoom-image shadow-lg" alt="Zoomed view">
                            </div>
                        </div>

                        {{-- Controles Flotantes --}}
                        <div class="zoom-controls">
                            <button type="button" class="zoom-btn" id="btnZoomIn" title="Acercar (Rueda Arriba)">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button type="button" class="zoom-btn" id="btnZoomOut" title="Alejar (Rueda Abajo)">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button type="button" class="zoom-btn" id="btnZoomReset" title="Restaurar Vista">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div style="width: 1px; background: #e5e7eb; margin: 0 5px;"></div>
                            <a href="#" id="zoomDownload" target="_blank" class="zoom-btn"
                                title="Ver SVG Completo" style="text-decoration: none;">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de Detalles de Producción (AJAX) --}}
        <div class="modal fade" id="viewProductionModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content" id="viewProductionModalContent"
                    style="border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: visible;">
                    {{-- El contenido se carga vía AJAX --}}
                    <div class="modal-body text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Fondo transparente y sin borde en el contenedor */
        #tablaProducciones_wrapper .dt-buttons {
            background-color: transparent;
            box-shadow: none;
            border: none;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        /* Estilo personalizado para los botones de exportación */
        #tablaProducciones_wrapper .dt-buttons .btn {
            color: #fff;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        /* Botones de acción circulares - estilo premium */
        .btn-action {
            cursor: pointer;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Colores por tipo de botón */
        .btn-danger {
            background-color: #dc2626;
            border: none;
        }

        .btn-success {
            background-color: #10b981;
            border: none;
        }

        .btn-info {
            background-color: #0284c7;
            border: none;
        }

        .btn-warning {
            background-color: #d97706;
            color: #fff;
            border: none;
        }

        .btn-default {
            background-color: #6b7280;
            color: #fff;
            border: none;
        }

        /* Tabla premium */
        #tablaProducciones {
            border-radius: 8px;
            overflow: hidden;
        }

        #tablaProducciones thead th {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        #tablaProducciones tbody tr {
            transition: background-color 0.15s ease;
        }

        #tablaProducciones tbody tr:hover {
            background-color: #f8fafc;
        }

        /* --- Estilos de Bordado Premium (Performance Bancaria) --- */
        .preview-wrapper {
            transition: all 0.3s ease;
        }

        .embroidery-thumbnail {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* Hover: Marco verde + levantamiento + sombra */
        .specs-wrapper:hover,
        .embroidery-thumbnail:hover {
            border-color: #10b981 !important;
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.2), 0 4px 6px -7px rgba(16, 185, 129, 0.1);
            z-index: 10;
        }

        .specs-wrapper {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            padding: 12px;
            border-radius: 10px;
            border: 2px solid #f3f4f6;
            background: #f9fafb;
        }

        /* Skeletons Pulsantes */
        .skeleton-loader {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: skeleton-pulse 1.5s infinite;
        }

        @keyframes skeleton-pulse {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* Controles de Zoom Avanzado */
        .zoom-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(4px);
        }

        .zoom-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: #374151;
            font-size: 16px;
        }

        .zoom-btn:hover {
            background: #f9fafb;
            border-color: #10b981;
            color: #10b981;
        }

        .zoom-container {
            cursor: grab;
            width: 100%;
            height: 100%;
        }

        .zoom-container:active {
            cursor: grabbing;
        }

        .zoom-wrapper {
            transition: transform 0.1s ease-out;
            transform-origin: center;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }

        .zoom-image {
            user-select: none;
            -webkit-user-drag: none;
            max-width: 90%;
            max-height: 80vh;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.1));
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .btn-action {
                width: 32px !important;
                height: 32px !important;
            }

            .btn-action i {
                font-size: 12px !important;
            }

            #tablaProducciones_wrapper .dt-buttons .btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>
@stop

@section('js')
    <script>
        $(function() {
            var table = $("#tablaProducciones").DataTable({
                "pageLength": 10,
                "language": {
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Producciones",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Producciones",
                    "infoFiltered": "(Filtrado de _MAX_ total Producciones)",
                    "lengthMenu": "Mostrar _MENU_ Producciones",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscador:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                buttons: [{
                        text: '<i class="fas fa-copy"></i> COPIAR',
                        extend: 'copy',
                        className: 'btn btn-default'
                    },
                    {
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        extend: 'pdf',
                        className: 'btn btn-danger'
                    },
                    {
                        text: '<i class="fas fa-file-csv"></i> CSV',
                        extend: 'csv',
                        className: 'btn btn-info'
                    },
                    {
                        text: '<i class="fas fa-file-excel"></i> EXCEL',
                        extend: 'excel',
                        className: 'btn btn-success'
                    },
                    {
                        text: '<i class="fas fa-print"></i> IMPRIMIR',
                        extend: 'print',
                        className: 'btn btn-default'
                    }
                ]
            });

            table.buttons().container().appendTo('#tablaProducciones_wrapper .row:eq(0)');

            // --- Performance Optimized: Lazy Loading (DataTables Compatible) ---
            const previewObserver = ('IntersectionObserver' in window) ? new IntersectionObserver((entries,
                observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.getAttribute('data-src');
                        if (src && !img.src) {
                            img.src = src;
                            img.onload = () => {
                                img.style.opacity = '1';
                                $(img).siblings('.skeleton-loader').fadeOut(300);
                            };
                        }
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '100px'
            }) : null;

            function initLazyLoading() {
                $('.lazy-preview').each(function() {
                    if (previewObserver) {
                        previewObserver.observe(this);
                    } else {
                        const src = $(this).attr('data-src');
                        if (src) {
                            $(this).attr('src', src).css('opacity', '1');
                            $(this).siblings('.skeleton-loader').hide();
                        }
                    }
                });
            }

            // Iniciar al cargar y en cada redibujado de la tabla (paginaciÃ³n, bÃºsqueda)
            initLazyLoading();
            table.on('draw', function() {
                initLazyLoading();
            });

            // --- LÃ³gica de Zoom Avanzado (Pan & Zoom) ---
            let currentScale = 1;
            let isDragging = false;
            let startX, startY, transX = 0,
                transY = 0;

            // Delegar click al wrapper para mejorar la captura del evento
            $(document).on('click', '.preview-wrapper', function() {
                const img = $(this).find('.embroidery-thumbnail');
                const src = img.attr('src') || img.attr('data-src');
                const name = img.data('name') || 'Vista Previa';

                if (src) {
                    $('#zoomImage').attr('src', src);
                    $('#zoomModalTitle').text(name);

                    // Asegurar que abrimos el explorador con herramientas en la pestaña nueva
                    const explorerUrl = src.includes('?') ? `${src}&explorer=1` : `${src}?explorer=1`;
                    $('#zoomDownload').attr('href', explorerUrl);

                    // Resetear estado
                    currentScale = 1;
                    transX = 0;
                    transY = 0;
                    updateTransform();

                    $('#zoomModal').modal('show');
                }
            });

            function updateTransform() {
                $('#zoomWrapper').css('transform', `scale(${currentScale}) translate(${transX}px, ${transY}px)`);
            }

            $('#btnZoomIn').on('click', function(e) {
                e.preventDefault();
                currentScale = Math.min(currentScale + 0.5, 5);
                updateTransform();
            });

            $('#btnZoomOut').on('click', function(e) {
                e.preventDefault();
                currentScale = Math.max(currentScale - 0.5, 0.5);
                updateTransform();
            });

            $('#btnZoomReset').on('click', function(e) {
                e.preventDefault();
                currentScale = 1;
                transX = 0;
                transY = 0;
                updateTransform();
            });

            // Paneo con el mouse
            $('#zoomContainer').on('mousedown', function(e) {
                if (currentScale > 1) {
                    isDragging = true;
                    startX = e.pageX - transX;
                    startY = e.pageY - transY;
                    $(this).css('cursor', 'grabbing');
                }
            });

            $(window).on('mousemove', function(e) {
                if (isDragging) {
                    transX = e.pageX - startX;
                    transY = e.pageY - startY;
                    updateTransform();
                }
            }).on('mouseup', function() {
                isDragging = false;
                $('#zoomContainer').css('cursor', currentScale > 1 ? 'grab' : 'default');
            });

            // Zoom rueda mouse
            $('#zoomContainer').on('wheel', function(e) {
                e.preventDefault();
                const delta = e.originalEvent.deltaY;
                const oldScale = currentScale;
                currentScale = delta > 0 ? Math.max(currentScale - 0.2, 0.5) : Math.min(currentScale + 0.2,
                    5);
                updateTransform();
            });

            // Resto de la lógica (detalles AJAX, filtros, etc.)
            $(document).on('click', '.btn-view-details', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                $('#viewProductionModal').modal('show');
                $('#viewProductionModalContent').html(
                    '<div class="modal-body text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                $.get(url, data => $('#viewProductionModalContent').html(data));
            });

            $('#filtroJerarquia').on('change', function() {
                const val = $(this).val();
                table.column(1).search(val === 'diseño' ? 'DISEÑO' : (val === 'variante' ? 'VARIANTE' : ''))
                    .draw();
            });

            $('#filtroEstado').on('change', function() {
                table.column(4).search($(this).val()).draw();
            });
            $('#limpiarFiltros').on('click', () => {
                $('#filtroJerarquia, #filtroEstado').val('');
                table.search('').columns().search('').draw();
            });

            // SweetAlert2 para eliminar producciones
            $(document).on('submit', '.form-eliminar', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡Eliminarás esta producción y no se podrá recuperar!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', // Rojo
                    cancelButtonColor: '#6c757d', // Gris (Secondary)
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
@stop
