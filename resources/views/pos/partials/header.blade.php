{{-- POS Header - Enterprise SaaS Design 2025 --}}
<header class="pos-header">
    {{-- Left Section: Navigation --}}
    <div class="pos-header-left">
        <a href="{{ route('home') }}" class="pos-header-back" title="Volver al sistema">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="pos-header-brand">
            <div class="pos-header-logo">
                <i class="fas fa-cash-register"></i>
            </div>
            <div class="pos-header-brand-text">
                <span class="pos-header-title">PUNTO DE VENTA</span>
                <span class="pos-header-subtitle">Sistema Bordados</span>
            </div>
        </div>
    </div>

    {{-- Right Section: Actions & Info --}}
    <div class="pos-header-right">
        {{-- History Button --}}
        <a href="{{ route('admin.pos-sales.index') }}" class="pos-header-btn pos-header-btn-primary">
            <i class="fas fa-receipt"></i>
            <span>Historial</span>
        </a>

        {{-- Date & Time --}}
        <div class="pos-header-datetime">
            <div class="pos-header-date">
                <i class="fas fa-calendar-alt"></i>
                <span>{{ now()->format('d M Y') }}</span>
            </div>
            <div class="pos-header-time" id="pos-clock">
                <i class="fas fa-clock"></i>
                <span id="pos-clock-time">{{ now()->format('H:i') }}</span>
            </div>
        </div>

        {{-- User Profile --}}
        <div class="pos-header-user">
            <div class="pos-header-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="pos-header-user-info">
                <span class="pos-header-user-name">{{ auth()->user()->name ?? 'Usuario' }}</span>
                <span class="pos-header-user-role">Vendedor</span>
            </div>
        </div>
    </div>
</header>

@push('styles')
<style>
    .pos-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 72px;
        padding: 0 var(--pos-space-lg);
        background: var(--pos-slate-900);
        border-bottom: 1px solid var(--pos-slate-700);
        position: relative;
        z-index: 100;
    }

    .pos-header-left,
    .pos-header-right {
        display: flex;
        align-items: center;
        gap: var(--pos-space-md);
    }

    /* Back Button */
    .pos-header-back {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: var(--pos-slate-800);
        border: 1px solid var(--pos-slate-700);
        border-radius: var(--pos-radius-md);
        color: var(--pos-slate-300);
        font-size: 16px;
        text-decoration: none;
        transition: var(--pos-transition);
    }

    .pos-header-back:hover {
        background: var(--pos-slate-700);
        color: var(--pos-white);
        transform: translateX(-2px);
    }

    /* Brand */
    .pos-header-brand {
        display: flex;
        align-items: center;
        gap: var(--pos-space-md);
        padding-left: var(--pos-space-md);
        border-left: 1px solid var(--pos-slate-700);
    }

    .pos-header-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, var(--pos-primary) 0%, var(--pos-primary-dark) 100%);
        border-radius: var(--pos-radius-md);
        color: var(--pos-white);
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }

    .pos-header-brand-text {
        display: flex;
        flex-direction: column;
    }

    .pos-header-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--pos-white);
        letter-spacing: 0.5px;
    }

    .pos-header-subtitle {
        font-size: 12px;
        font-weight: 500;
        color: var(--pos-slate-400);
    }

    /* Header Buttons */
    .pos-header-btn {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        padding: 10px 16px;
        border-radius: var(--pos-radius-md);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--pos-transition);
    }

    .pos-header-btn i {
        font-size: 14px;
    }

    .pos-header-btn-primary {
        background: linear-gradient(135deg, var(--pos-primary) 0%, var(--pos-primary-dark) 100%);
        color: var(--pos-white);
        border: 1px solid var(--pos-primary-light);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }

    .pos-header-btn-primary:hover {
        background: linear-gradient(135deg, var(--pos-primary-light) 0%, var(--pos-primary) 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        color: var(--pos-white);
    }

    /* DateTime */
    .pos-header-datetime {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        padding: 8px 16px;
        background: var(--pos-slate-800);
        border: 1px solid var(--pos-slate-700);
        border-radius: var(--pos-radius-md);
    }

    .pos-header-date,
    .pos-header-time {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--pos-slate-300);
    }

    .pos-header-date i,
    .pos-header-time i {
        font-size: 12px;
        color: var(--pos-slate-400);
    }

    .pos-header-time {
        padding-left: var(--pos-space-sm);
        border-left: 1px solid var(--pos-slate-600);
        color: var(--pos-success);
    }

    .pos-header-time i {
        color: var(--pos-success);
    }

    /* User Profile */
    .pos-header-user {
        display: flex;
        align-items: center;
        gap: var(--pos-space-sm);
        padding: 6px 12px 6px 6px;
        background: var(--pos-slate-800);
        border: 1px solid var(--pos-slate-700);
        border-radius: var(--pos-radius-full);
    }

    .pos-header-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, var(--pos-success) 0%, var(--pos-success-dark) 100%);
        border-radius: var(--pos-radius-full);
        font-size: 14px;
        font-weight: 700;
        color: var(--pos-white);
    }

    .pos-header-user-info {
        display: flex;
        flex-direction: column;
    }

    .pos-header-user-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--pos-white);
        line-height: 1.2;
    }

    .pos-header-user-role {
        font-size: 11px;
        font-weight: 500;
        color: var(--pos-slate-400);
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .pos-header {
            padding: 0 var(--pos-space-md);
        }

        .pos-header-brand-text {
            display: none;
        }

        .pos-header-btn span {
            display: none;
        }

        .pos-header-datetime {
            padding: 8px 12px;
        }

        .pos-header-date span {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .pos-header {
            height: 64px;
            flex-wrap: nowrap;
        }

        .pos-header-datetime {
            display: none;
        }

        .pos-header-user-info {
            display: none;
        }

        .pos-header-brand {
            padding-left: var(--pos-space-sm);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function() {
        function updateClock() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var el = document.getElementById('pos-clock-time');
            if (el) {
                el.textContent = hours + ':' + minutes;
            }
        }
        updateClock();
        setInterval(updateClock, 30000);
    })();
</script>
@endpush
