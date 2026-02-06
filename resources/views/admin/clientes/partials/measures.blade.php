@php
    // Obtener medidas del historial o campos directos
    $measurements = $cliente->latest_measurements;
    $hasMeasures = !empty(array_filter($measurements, fn($v) => !empty($v) && $v !== '0'));

    // Obtener información de trazabilidad del último registro
    $latestHistory = $cliente->measurementHistory()->orderBy('captured_at', 'desc')->first();

    // Obtener todo el historial ordenado descendentemente
    $allHistory = $cliente->measurementHistory()->with(['order', 'product'])->orderBy('captured_at', 'desc')->get();

    $sourceLabels = [
        'order' => ['label' => 'Pedido', 'icon' => 'fa-shopping-cart'],
        'manual' => ['label' => 'Manual', 'icon' => 'fa-user-edit'],
        'import' => ['label' => 'Importado', 'icon' => 'fa-file-import'],
    ];
    $sourceInfo = $latestHistory ? ($sourceLabels[$latestHistory->source] ?? ['label' => 'Desconocido', 'icon' => 'fa-question']) : null;

    // Labels para medidas
    $measurementLabels = [
        'busto' => 'Busto',
        'cintura' => 'Cintura',
        'cadera' => 'Cadera',
        'alto_cintura' => 'Alto Cintura',
        'largo' => 'Largo',
        'largo_vestido' => 'Largo Vestido',
    ];
@endphp

@if (!$hasMeasures && $allHistory->isEmpty())
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No hay medidas registradas para este cliente.
    </div>
@else
    <style>
        /* === TABS === */
        .measures-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 16px;
        }

        .measures-tab {
            flex: 1;
            padding: 12px 16px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #6c757d;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s ease;
        }

        .measures-tab:hover {
            color: #6f42c1;
            background: #f8f9fa;
        }

        .measures-tab.active {
            color: #6f42c1;
            border-bottom-color: #6f42c1;
        }

        .measures-tab i {
            margin-right: 6px;
        }

        .tab-content-measures {
            display: none;
        }

        .tab-content-measures.active {
            display: block;
        }

        /* === CARDS MEDIDAS === */
        .medida-card-view {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 12px 14px;
            background: #ffffff;
            transition: box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
            position: relative;
        }

        .medida-card-view:hover {
            transform: translateY(-4px);
            border-color: rgba(247, 0, 255, 0.779);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.06), 0 4px 8px rgba(0, 0, 0, 0.04);
        }

        .medida-card-view .medida-img {
            width: 100%;
            max-height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .medida-card-view:hover .medida-img {
            transform: scale(1.03);
            opacity: 0.95;
        }

        .medida-card-view .medida-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #6f42c1;
            margin: 0;
        }

        .medida-card-view .medida-value.no-data {
            color: #adb5bd;
            font-size: 1rem;
        }

        .medida-label-view {
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
            color: #6f42c1;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .traceability-info {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #6f42c1;
        }

        .traceability-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            background: #f3e5f5;
            color: #6f42c1;
        }

        /* === HISTORIAL === */
        .history-scroll-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .history-scroll-container::-webkit-scrollbar {
            width: 6px;
        }

        .history-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .history-scroll-container::-webkit-scrollbar-thumb {
            background: #6f42c1;
            border-radius: 3px;
        }

        .history-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            transition: border-color 0.2s ease;
        }

        .history-item:hover {
            border-color: #6f42c1;
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .history-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            background: #f3e5f5;
            color: #6f42c1;
        }

        .history-measurements {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .history-measure {
            background: #f8f9fa;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            color: #495057;
        }

        .history-measure span {
            color: #6f42c1;
            font-weight: 700;
            font-size: 17px;
        }

        .history-empty {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .history-empty i {
            font-size: 32px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
    </style>

    {{-- TABS --}}
    <div class="measures-tabs">
        <button type="button" class="measures-tab active" data-tab="current">
            <i class="fas fa-ruler-combined"></i> Medidas Actuales
        </button>
        <button type="button" class="measures-tab" data-tab="history">
            <i class="fas fa-history"></i> Historial
            @if($allHistory->count() > 0)
                <span class="badge badge-pill" style="background: #6f42c1; color: white; font-size: 11px; margin-left: 4px;">{{ $allHistory->count() }}</span>
            @endif
        </button>
    </div>

    {{-- TAB: MEDIDAS ACTUALES --}}
    <div class="tab-content-measures active" data-content="current">
        @if($hasMeasures)
            {{-- Trazabilidad --}}
            @if($latestHistory && $sourceInfo)
                <div class="traceability-info">
                    <span class="traceability-item">
                        <i class="fas {{ $sourceInfo['icon'] }}"></i> {{ $sourceInfo['label'] }}
                    </span>
                    <span class="traceability-item">
                        <i class="fas fa-calendar-alt"></i> {{ $latestHistory->captured_at->format('d/m/Y H:i') }}
                    </span>
                    @if($latestHistory->order)
                        <span class="traceability-item">
                            <i class="fas fa-file-invoice"></i> {{ $latestHistory->order->order_number }}
                        </span>
                    @endif
                    @if($latestHistory->product)
                        <span class="traceability-item">
                            <i class="fas fa-tshirt"></i> {{ $latestHistory->product->name }}
                        </span>
                    @endif
                </div>
            @endif

            <div class="row">
                {{-- BUSTO --}}
                <div class="col-md-4 col-6 mb-3 text-center">
                    <label class="medida-label-view">BUSTO</label>
                    <div class="medida-card-view">
                        <img src="{{ asset('images/busto.png') }}" alt="Busto" class="medida-img">
                        @if(!empty($measurements['busto']))
                            <p class="medida-value">{{ $measurements['busto'] }} cm</p>
                        @else
                            <p class="medida-value no-data">--</p>
                        @endif
                    </div>
                </div>

                {{-- ALTO CINTURA --}}
                <div class="col-md-4 col-6 mb-3 text-center">
                    <label class="medida-label-view">ALTO CINTURA</label>
                    <div class="medida-card-view">
                        <img src="{{ asset('images/alto_cintura.png') }}" alt="Alto Cintura" class="medida-img">
                        @if(!empty($measurements['alto_cintura']))
                            <p class="medida-value">{{ $measurements['alto_cintura'] }} cm</p>
                        @else
                            <p class="medida-value no-data">--</p>
                        @endif
                    </div>
                </div>

                {{-- CINTURA --}}
                <div class="col-md-4 col-6 mb-3 text-center">
                    <label class="medida-label-view">CINTURA</label>
                    <div class="medida-card-view">
                        <img src="{{ asset('images/cintura.png') }}" alt="Cintura" class="medida-img">
                        @if(!empty($measurements['cintura']))
                            <p class="medida-value">{{ $measurements['cintura'] }} cm</p>
                        @else
                            <p class="medida-value no-data">--</p>
                        @endif
                    </div>
                </div>

                {{-- CADERA --}}
                <div class="col-md-4 col-6 mb-3 text-center">
                    <label class="medida-label-view">CADERA</label>
                    <div class="medida-card-view">
                        <img src="{{ asset('images/cadera.png') }}" alt="Cadera" class="medida-img">
                        @if(!empty($measurements['cadera']))
                            <p class="medida-value">{{ $measurements['cadera'] }} cm</p>
                        @else
                            <p class="medida-value no-data">--</p>
                        @endif
                    </div>
                </div>

                {{-- LARGO BLUSA --}}
                <div class="col-md-4 col-6 mb-3 text-center">
                    <label class="medida-label-view">LARGO BLUSA</label>
                    <div class="medida-card-view">
                        <img src="{{ asset('images/largo.png') }}" alt="Largo Blusa" class="medida-img">
                        @if(!empty($measurements['largo']))
                            <p class="medida-value">{{ $measurements['largo'] }} cm</p>
                        @else
                            <p class="medida-value no-data">--</p>
                        @endif
                    </div>
                </div>

                {{-- LARGO VESTIDO --}}
                <div class="col-md-4 col-6 mb-3 text-center">
                    <label class="medida-label-view">LARGO VESTIDO</label>
                    <div class="medida-card-view">
                        <img src="{{ asset('images/largo_vestido.png') }}" alt="Largo Vestido" class="medida-img">
                        @if(!empty($measurements['largo_vestido']))
                            <p class="medida-value">{{ $measurements['largo_vestido'] }} cm</p>
                        @else
                            <p class="medida-value no-data">--</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No hay medidas actuales registradas.
            </div>
        @endif
    </div>

    {{-- TAB: HISTORIAL --}}
    <div class="tab-content-measures" data-content="history">
        @if($allHistory->count() > 0)
            <div class="history-scroll-container">
                @foreach($allHistory as $index => $record)
                    @php
                        $recSourceInfo = $sourceLabels[$record->source] ?? ['label' => 'Desconocido', 'icon' => 'fa-question'];
                        $recMeasurements = $record->measurements ?? [];
                    @endphp
                    <div class="history-item {{ $index === 0 ? 'border-primary' : '' }}">
                        <div class="history-header">
                            @if($index === 0)
                                <span class="history-badge" style="background: #6f42c1; color: white;">
                                    <i class="fas fa-star"></i> Actual
                                </span>
                            @endif
                            <span class="history-badge">
                                <i class="fas {{ $recSourceInfo['icon'] }}"></i> {{ $recSourceInfo['label'] }}
                            </span>
                            <span class="history-badge">
                                <i class="fas fa-calendar-alt"></i> {{ $record->captured_at->format('d/m/Y H:i') }}
                            </span>
                            @if($record->order)
                                <span class="history-badge">
                                    <i class="fas fa-file-invoice"></i> {{ $record->order->order_number }}
                                </span>
                            @endif
                            @if($record->product)
                                <span class="history-badge">
                                    <i class="fas fa-tshirt"></i> {{ $record->product->name }}
                                </span>
                            @endif
                        </div>
                        <div class="history-measurements">
                            @foreach($measurementLabels as $key => $label)
                                @if(!empty($recMeasurements[$key]) && $recMeasurements[$key] !== '0')
                                    <div class="history-measure">
                                        {{ $label }}: <span>{{ $recMeasurements[$key] }} cm</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="history-empty">
                <i class="fas fa-history d-block"></i>
                <p class="mb-0">No hay historial de medidas registrado.</p>
            </div>
        @endif
    </div>

    <script>
        (function() {
            document.querySelectorAll('.measures-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var targetTab = this.getAttribute('data-tab');

                    // Quitar active de todos los tabs y contenidos
                    document.querySelectorAll('.measures-tab').forEach(function(t) {
                        t.classList.remove('active');
                    });
                    document.querySelectorAll('.tab-content-measures').forEach(function(c) {
                        c.classList.remove('active');
                    });

                    // Activar el tab y contenido seleccionado
                    this.classList.add('active');
                    document.querySelector('[data-content="' + targetTab + '"]').classList.add('active');
                });
            });
        })();
    </script>
@endif
