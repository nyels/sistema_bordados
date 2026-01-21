@php
    $hasMeasures =
        $cliente->busto || $cliente->alto_cintura || $cliente->cintura ||
        $cliente->cadera || $cliente->largo || $cliente->largo_vestido;
@endphp

@if (!$hasMeasures)
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No hay medidas registradas para este cliente.
    </div>
@else
    <style>
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
    </style>

    <div class="row">
        {{-- BUSTO --}}
        <div class="col-md-4 col-6 mb-3 text-center">
            <label class="medida-label-view">BUSTO</label>
            <div class="medida-card-view">
                <img src="{{ asset('images/busto.png') }}" alt="Busto" class="medida-img">
                @if($cliente->busto)
                    <p class="medida-value">{{ $cliente->busto }} cm</p>
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
                @if($cliente->alto_cintura)
                    <p class="medida-value">{{ $cliente->alto_cintura }} cm</p>
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
                @if($cliente->cintura)
                    <p class="medida-value">{{ $cliente->cintura }} cm</p>
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
                @if($cliente->cadera)
                    <p class="medida-value">{{ $cliente->cadera }} cm</p>
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
                @if($cliente->largo)
                    <p class="medida-value">{{ $cliente->largo }} cm</p>
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
                @if($cliente->largo_vestido)
                    <p class="medida-value">{{ $cliente->largo_vestido }} cm</p>
                @else
                    <p class="medida-value no-data">--</p>
                @endif
            </div>
        </div>
    </div>
@endif
