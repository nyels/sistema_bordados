@php
    $hasMeasures =
        $cliente->busto || $cliente->alto_cintura || $cliente->cintura || $cliente->cadera || $cliente->largo;
@endphp

@if (!$hasMeasures)
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> No hay medidas registradas para este cliente.
    </div>
@else
    <div class="row">
        {{-- BUSTO --}}
        <div class="col-md-6 mb-4 text-center">
            <h6 class="font-weight-bold text-uppercase">Busto</h6>
            <div class="p-3 border rounded bg-white shadow-sm">
                <img src="{{ asset('images/busto.png') }}" alt="Busto" class="img-fluid mb-2" style="max-height: 150px;">
                <h5 class="text-primary font-weight-bold m-0">{{ $cliente->busto ?? '--' }} cm</h5>
            </div>
        </div>

        {{-- ALTO CINTURA --}}
        <div class="col-md-6 mb-4 text-center">
            <h6 class="font-weight-bold text-uppercase">Alto Cintura</h6>
            <div class="p-3 border rounded bg-white shadow-sm">
                <img src="{{ asset('images/alto_cintura.png') }}" alt="Alto Cintura" class="img-fluid mb-2"
                    style="max-height: 150px;">
                <h5 class="text-primary font-weight-bold m-0">{{ $cliente->alto_cintura ?? '--' }} cm</h5>
            </div>
        </div>

        {{-- CINTURA --}}
        <div class="col-md-6 mb-4 text-center">
            <h6 class="font-weight-bold text-uppercase">Cintura</h6>
            <div class="p-3 border rounded bg-white shadow-sm">
                <img src="{{ asset('images/cintura.png') }}" alt="Cintura" class="img-fluid mb-2"
                    style="max-height: 150px;">
                <h5 class="text-primary font-weight-bold m-0">{{ $cliente->cintura ?? '--' }} cm</h5>
            </div>
        </div>

        {{-- CADERA --}}
        <div class="col-md-6 mb-4 text-center">
            <h6 class="font-weight-bold text-uppercase">Cadera</h6>
            <div class="p-3 border rounded bg-white shadow-sm">
                <img src="{{ asset('images/cadera.png') }}" alt="Cadera" class="img-fluid mb-2"
                    style="max-height: 150px;">
                <h5 class="text-primary font-weight-bold m-0">{{ $cliente->cadera ?? '--' }} cm</h5>
            </div>
        </div>

        {{-- LARGO --}}
        <div class="col-md-6 mb-4 text-center mx-auto">
            <h6 class="font-weight-bold text-uppercase">Largo</h6>
            <div class="p-3 border rounded bg-white shadow-sm">
                <img src="{{ asset('images/largo.png') }}" alt="Largo" class="img-fluid mb-2"
                    style="max-height: 150px;">
                <h5 class="text-primary font-weight-bold m-0">{{ $cliente->largo ?? '--' }} cm</h5>
            </div>
        </div>
    </div>
@endif
