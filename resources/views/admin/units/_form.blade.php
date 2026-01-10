{{-- Nombre y Símbolo --}}
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name" class="font-weight-bold">
                Nombre <span class="text-danger">*</span>
            </label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $unit->name ?? '') }}" placeholder="Ej: Metro, Rollo, Cono" required autofocus>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="symbol" class="font-weight-bold">
                Símbolo <span class="text-danger">*</span>
            </label>
            <input type="text" name="symbol" id="symbol"
                class="form-control @error('symbol') is-invalid @enderror"
                value="{{ old('symbol', $unit->symbol ?? '') }}" placeholder="Ej: m, pz, cono" required>
            @error('symbol')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">Abreviación corta</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="sort_order" class="font-weight-bold">Orden</label>
            <input type="number" name="sort_order" id="sort_order"
                class="form-control @error('sort_order') is-invalid @enderror"
                value="{{ old('sort_order', $unit->sort_order ?? 0) }}" min="0" max="999">
            @error('sort_order')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

{{-- Opciones --}}
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card bg-light border-0">
            <div class="card-body">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_base" name="is_base" value="1"
                        {{ old('is_base', $unit->is_base ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="is_base">
                        Unidad Base
                    </label>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Las unidades base (metro, pieza) se usan para inventario y consumo.
                    Las unidades de compra (rollo, caja) se convierten a unidades base.
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-light border-0">
            <div class="card-body">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                        {{ old('is_active', $unit->is_active ?? true) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="is_active">
                        Activo
                    </label>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Las unidades inactivas no aparecen en los selectores del sistema.
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Unidad Base Compatible (solo para unidades de compra) --}}
<div class="row mt-3" id="compatible_unit_section" style="{{ old('is_base', $unit->is_base ?? false) ? 'display: none;' : '' }}">
    <div class="col-md-6">
        <div class="form-group">
            <label for="compatible_base_unit_id" class="font-weight-bold">
                <i class="fas fa-link text-info mr-1"></i>
                Compatible con Unidad Base
            </label>
            <select name="compatible_base_unit_id" id="compatible_base_unit_id"
                class="form-control @error('compatible_base_unit_id') is-invalid @enderror">
                <option value="">-- Seleccionar unidad base --</option>
                @foreach($baseUnits ?? [] as $baseUnit)
                    <option value="{{ $baseUnit->id }}"
                        {{ old('compatible_base_unit_id', $unit->compatible_base_unit_id ?? '') == $baseUnit->id ? 'selected' : '' }}>
                        {{ $baseUnit->name }} ({{ $baseUnit->symbol }})
                    </option>
                @endforeach
            </select>
            @error('compatible_base_unit_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Indica a qué unidad base se puede convertir esta unidad de compra.
                <br>Ejemplo: ROLLO 50M es compatible con METRO.
            </small>
        </div>
    </div>
</div>

@push('js')
<script>
$(function() {
    // Mostrar/ocultar sección de unidad compatible según el checkbox
    $('#is_base').on('change', function() {
        if ($(this).is(':checked')) {
            $('#compatible_unit_section').slideUp();
            $('#compatible_base_unit_id').val('');
        } else {
            $('#compatible_unit_section').slideDown();
        }
    });
});
</script>
@endpush
