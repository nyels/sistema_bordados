{{-- Product Search - Enterprise SaaS Design 2025 --}}
<div class="pos-search-section">
    <div class="pos-search-wrapper">
        <i class="fas fa-search pos-search-icon"></i>
        <input type="text"
               id="pos-search"
               placeholder="Buscar producto por nombre o SKU..."
               class="pos-search-input"
               autocomplete="off">
        <div class="pos-search-shortcut">
            <kbd>Ctrl</kbd><span>+</span><kbd>K</kbd>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pos-search-section {
        padding: var(--pos-space-md) var(--pos-space-lg);
        background: var(--pos-slate-800);
        border-bottom: 1px solid var(--pos-slate-700);
    }

    .pos-search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .pos-search-icon {
        position: absolute;
        left: 18px;
        font-size: 16px;
        color: var(--pos-slate-400);
        pointer-events: none;
        z-index: 1;
    }

    .pos-search-input {
        width: 100%;
        height: 52px;
        padding: 0 140px 0 52px;
        background: var(--pos-slate-900);
        border: 2px solid var(--pos-slate-700);
        border-radius: var(--pos-radius-md);
        font-size: 15px;
        font-weight: 500;
        color: var(--pos-white);
        transition: var(--pos-transition);
    }

    .pos-search-input::placeholder {
        color: var(--pos-slate-500);
        font-weight: 400;
    }

    .pos-search-input:hover {
        border-color: var(--pos-slate-600);
    }

    .pos-search-input:focus {
        outline: none;
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    .pos-search-shortcut {
        position: absolute;
        right: 16px;
        display: flex;
        align-items: center;
        gap: 4px;
        color: var(--pos-slate-500);
        font-size: 12px;
        pointer-events: none;
    }

    .pos-search-shortcut kbd {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        padding: 0 6px;
        background: var(--pos-slate-700);
        border: 1px solid var(--pos-slate-600);
        border-radius: 4px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
        color: var(--pos-slate-300);
    }

    .pos-search-shortcut span {
        color: var(--pos-slate-600);
    }

    @media (max-width: 768px) {
        .pos-search-section {
            padding: var(--pos-space-sm) var(--pos-space-md);
        }

        .pos-search-input {
            height: 48px;
            padding-right: 16px;
            font-size: 14px;
        }

        .pos-search-shortcut {
            display: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            var searchInput = document.getElementById('pos-search');
            if (searchInput) searchInput.focus();
        }
    });
</script>
@endpush
