{{-- Customer Selector - Premium Apple/SaaS Style --}}
<div class="pos-customer-section">
    <label for="customer-select" class="pos-section-label">
        <svg class="pos-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        Cliente
    </label>
    <div class="pos-select-wrapper">
        <select id="customer-select" class="pos-select">
            <option value="">VENTA LIBRE (Público General)</option>
            @foreach($clientes ?? [] as $cliente)
                <option value="{{ $cliente->id }}">
                    {{ $cliente->nombre }} {{ $cliente->apellido ?? '' }}
                    @if($cliente->rfc)
                        - RFC: {{ $cliente->rfc }}
                    @endif
                </option>
            @endforeach
        </select>
        <div class="pos-select-arrow">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pos-customer-section {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .pos-section-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    .pos-section-icon {
        width: 18px;
        height: 18px;
    }

    .pos-select-wrapper {
        position: relative;
    }

    .pos-select {
        width: 100%;
        height: 48px;
        padding: 0 44px 0 16px;
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 500;
        color: #1e293b;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pos-select:hover {
        border-color: #cbd5e1;
    }

    .pos-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .pos-select-arrow {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: #94a3b8;
        pointer-events: none;
    }

    .pos-select-arrow svg {
        width: 100%;
        height: 100%;
    }
</style>
@endpush
