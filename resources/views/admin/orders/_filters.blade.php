{{-- Filtros AJAX - NO submit, NO reload --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            {{-- Estado --}}
            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                <select id="filter-status" class="form-control form-control-sm">
                    <option value="">-- Estado --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Borrador</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmado</option>
                    <option value="in_production" {{ request('status') == 'in_production' ? 'selected' : '' }}>En Produccion</option>
                    <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Listo</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Entregado</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>

            {{-- Pago --}}
            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                <select id="filter-payment" class="form-control form-control-sm">
                    <option value="">-- Pago --</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Parcial</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Pagado</option>
                </select>
            </div>

            {{-- Urgencia --}}
            <div class="col-md-2 col-sm-6 mb-2 mb-md-0">
                <select id="filter-urgency" class="form-control form-control-sm">
                    <option value="">-- Urgencia --</option>
                    <option value="normal" {{ request('urgency') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="urgente" {{ request('urgency') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                    <option value="express" {{ request('urgency') == 'express' ? 'selected' : '' }}>Express</option>
                </select>
            </div>

            {{-- Toggles --}}
            <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" id="filter-blocked"
                            class="btn btn-outline-danger filter-toggle {{ request('blocked') ? 'active' : '' }}">
                        <i class="fas fa-ban"></i> Bloqueados
                    </button>
                    <button type="button" id="filter-delayed"
                            class="btn btn-outline-warning filter-toggle {{ request('delayed') ? 'active' : '' }}">
                        <i class="fas fa-clock"></i> Retrasados
                    </button>
                </div>
            </div>

            {{-- Limpiar --}}
            <div class="col-md-2 col-sm-12 text-right">
                <button type="button" id="filter-clear" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </div>
        </div>
    </div>
</div>
