{{-- POS Header - Premium Apple/SaaS Style --}}
<header class="pos-header">
    <div class="pos-header-left">
        <a href="{{ route('home') }}" class="pos-header-back">
            <svg class="pos-header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>SALIR</span>
        </a>
        <div class="pos-header-divider"></div>
        <h1 class="pos-header-title">PUNTO DE VENTA</h1>
    </div>

    <div class="pos-header-right">
        {{-- Botón Historial de Ventas --}}
        <a href="{{ route('admin.pos-sales.index') }}" class="pos-header-btn pos-header-btn-history">
            <svg class="pos-header-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>HISTORIAL</span>
        </a>

        {{-- Botón Fecha --}}
        <div class="pos-header-btn pos-header-btn-date">
            <svg class="pos-header-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>{{ now()->format('d/m/Y') }}</span>
        </div>

        {{-- Botón Reloj --}}
        <div class="pos-header-btn pos-header-btn-clock" id="pos-clock">
            <svg class="pos-header-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span id="pos-clock-time">{{ now()->format('H:i:s') }}</span>
        </div>

        <div class="pos-header-user">
            <div class="pos-header-avatar">
                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
            </div>
            <span>{{ auth()->user()->name ?? 'Usuario' }}</span>
        </div>
    </div>
</header>

@push('styles')
<style>
    .pos-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 64px;
        padding: 0 24px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .pos-header-left,
    .pos-header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pos-header-back {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .pos-header-back:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateX(-2px);
    }

    .pos-header-icon {
        width: 18px;
        height: 18px;
    }

    .pos-header-divider {
        width: 1px;
        height: 32px;
        background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.3), transparent);
    }

    .pos-header-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Botones estilizados del header */
    .pos-header-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: default;
    }

    .pos-header-btn-icon {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    /* Historial - Botón principal azul */
    .pos-header-btn-history {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #60a5fa;
        color: #fff;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }

    .pos-header-btn-history:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border-color: #93c5fd;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        color: #fff;
    }

    /* Fecha - Botón info sutil */
    .pos-header-btn-date {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    /* Reloj - Botón destacado verde */
    .pos-header-btn-clock {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid #34d399;
        color: #fff;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        min-width: 130px;
        justify-content: center;
    }

    .pos-header-user {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 14px 6px 6px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 25px;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
    }

    .pos-header-avatar {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
    }

    @media (max-width: 1024px) {
        .pos-header {
            padding: 0 16px;
            gap: 12px;
        }
        .pos-header-title {
            font-size: 18px;
        }
        .pos-header-info {
            padding: 6px 10px;
            font-size: 13px;
        }
    }

    @media (max-width: 768px) {
        .pos-header {
            flex-wrap: wrap;
            height: auto;
            padding: 12px 16px;
            gap: 12px;
        }
        .pos-header-left,
        .pos-header-right {
            width: 100%;
            justify-content: center;
        }
        .pos-header-divider {
            display: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('pos-clock-time').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateClock, 1000);
</script>
@endpush
