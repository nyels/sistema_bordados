{{-- Filtros AJAX - NO submit, NO reload --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            {{-- Prioridad --}}
            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                <select id="filter-urgency" class="form-control" style="font-size: 14px; color: #212529;">
                    <option value="">-- Selecciona Prioridad --</option>
                    <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="urgente" {{ request('urgency') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                    <option value="express" {{ request('urgency') == 'express' ? 'selected' : '' }}>Express</option>
                </select>
            </div>

            {{-- Estado --}}
            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                <select id="filter-status" class="form-control" style="font-size: 14px; color: #212529;">
                    <option value="">-- Selecciona Estado --</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Entregados
                    </option>
                </select>
            </div>

            {{-- Pago --}}
            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                <select id="filter-payment" class="form-control" style="font-size: 14px; color: #212529;">
                    <option value="">-- Selecciona Pago --</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pendiente
                    </option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Parcial
                    </option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Pagado
                    </option>
                </select>
            </div>

            {{-- Limpiar --}}
            <div class="col-md-auto col-sm-12">
                <button type="button" id="filter-clear" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </div>
        </div>
    </div>
</div>
